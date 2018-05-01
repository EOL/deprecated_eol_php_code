<?php
namespace php_active_record;
/*

This can be a generic connector for CSV DwCA resources.
added for e.g. http://britishbryozoans.myspecies.info/eol-dwca.zip

*/
class CSV2DwCA_Utility_generic
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
        
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",
                                  
                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference",
                                  "http://eol.org/schema/reference/Reference"       => "reference",
                                  "http://rs.gbif.org/terms/1.0/Reference"          => "reference",
                                  "http://rs.tdwg.org/dwc/terms/Taxon"              => "taxon",
                                  "http://rs.gbif.org/terms/1.0/VernacularName"     => "vernacular",
                                  "http://eol.org/schema/agent/Agent"               => "agent",
                                  "http://eol.org/schema/media/Document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/Distribution"       => "document",
                                  "http://rs.gbif.org/terms/1.0/Image"              => "document",
                                  "http://rs.gbif.org/terms/1.0/Description"        => "document"
                                  );
    }

    private function start()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        if(!$paths) return false;
        // print_r($paths); exit;
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }

    function convert_archive()
    {
        if(!($info = self::start())) return false;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        // print_r($tables); exit;

        echo "\nConverting CSV archive to EOL DwCA...\n";
        foreach($tables as $key => $values) {
            // $tbl = $values[0]; //orig row
            foreach($values as $tbl) {
                // print_r($tbl); exit;
                echo "\n process row_type: $tbl->row_type -- ";
                if($class = @$this->extensions[$tbl->row_type]) //process only defined row_types
                {
                    echo "\n -- Processing [$tbl->file_uri] [$class]...\n";
                    self::process_extension($tbl->file_uri, $class, $tbl);
                }
                else {
                    if(in_array($tbl->row_type, array("http://rs.gbif.org/terms/1.0/TypesAndSpecimen", "http://www.w3.org/ns/oa#Annotationt"))) $debug['undefined row_type'][$tbl->row_type] = '';
                    else exit("\nInvalid row_type [$tbl->row_type]\n");
                }
            }
            
        }
        
        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
        return true;
    }

    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
        // return Functions::remove_whitespace($html);
    }
    
    private function process_extension($csv_file, $class, $tbl)
    {
        $fields = array();
        if($tbl->ignore_header_lines == 0 || $tbl->ignore_header_lines == false) {
            foreach($tbl->fields as $f) {
                $field = pathinfo($f['term'], PATHINFO_FILENAME);
                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                $fields[] = $field;
            }
            $count = count($fields);
        } 
        
        
        // start customization ----------------------------------------------------------------------------------
        if($this->resource_id == 809 && $tbl->row_type == "http://rs.gbif.org/terms/1.0/Image" && $tbl->location == "image.txt") {
            $fields = array();
            $fields[0] = 'identifier';
            $fields[1] = 'taxonID';
            $fields[2] = 'accessURI';
            $fields[3] = 'format';
            $fields[4] = 'UsageTerms';
            $fields[5] = "title";
            $fields[6] = "agentID";
            $fields[7] = "type";
            $fields[8] = "";
            $fields[9] = "";
            $fields[10] = "furtherInformationURL";
            $count = count($fields);
            // print_r($tbl); exit;
            $tbl->fields = array();
            $tbl->fields[0] = array('term' => 'http://purl.org/dc/terms/identifier', 'type' => '',  'default' => '');
            $tbl->fields[1] = array('term' => 'http://rs.tdwg.org/dwc/terms/taxonID', 'default' => '');
            $tbl->fields[2] = array('term' => 'http://rs.tdwg.org/ac/terms/accessURI', 'default' => '');
            $tbl->fields[3] = array('term' => 'http://purl.org/dc/terms/format', 'type' => '', 'default' => ''); 
            $tbl->fields[4] = array('term' => 'http://ns.adobe.com/xap/1.0/rights/UsageTerms', 'type' => '', 'default' => ''); 
            $tbl->fields[5] = array('term' => 'http://purl.org/dc/terms/title', 'type' => '', 'default' => ''); 
            $tbl->fields[6] = array('term' => 'http://eol.org/schema/agent/agentID', 'type' => '', 'default' => ''); 
            $tbl->fields[7] = array('term' => 'http://purl.org/dc/terms/type', 'type' => '', 'default' => ''); 
            $tbl->fields[8] = array('term' => '', 'type' => '', 'default' => ''); 
            $tbl->fields[9] = array('term' => '', 'type' => '', 'default' => ''); 
            $tbl->fields[10] = array('term' => 'http://rs.tdwg.org/ac/terms/furtherInformationURL', 'type' => '', 'default' => ''); 
            /*
            <coreid index="1" />
            <field index="0" term="http://purl.org/dc/terms/identifier" />
            <field index="1" term="http://rs.tdwg.org/dwc/terms/taxonID" />
            <field index="2" term="http://rs.tdwg.org/ac/terms/accessURI" />
            <field index="3" term="http://purl.org/dc/terms/format" />
            <field index="4" term="http://ns.adobe.com/xap/1.0/rights/UsageTerms" />
            <field index="5" term="http://purl.org/dc/terms/title" />
            <field index="6" term="http://eol.org/schema/agent/agentID" />
            <field index="7" term="http://purl.org/dc/terms/type" />
            <field index="8" term="http://rs.tdwg.org/ac/terms/furtherInformationURL" />
            */
            /*  in meta XML but erroneous!
                [0] => taxonID
                [1] => identifier
                [2] => format
                [3] => license
                [4] => title
                [5] => source
                [6] => rights
            Array(
                [0] => ffefde5f-a826-4713-a140-71ceb4b26af1
                [1] => 8f3c7612-6eaa-4682-a65b-f1c2af28f1ec
                [2] => http://bio.acousti.ca/sites/bio.acousti.ca/files/1646.WAV
                [3] => audio/x-wav
                [4] => //creativecommons.org/licenses/by-nc-sa/3.0/
                [5] => 1646.WAV
                [6] => ac2a0544-7322-4cf7-8eb9-c6e88479669e
                [7] => http://purl.org/dc/dcmitype/Sound
                [8] => http://sounds.myspecies.info/file/25089
                [9] =>  
                [10] => http://bio.acousti.ca/content/italy-e-schluderns-lab-recording-2471990-25%C2%B0c-60-w-bulb-heat-kenwood-kx880hx-akg-d202-tape
            )*/
        }
        
        if($this->resource_id == 809 && $tbl->row_type == "http://eol.org/schema/media/Document" && $tbl->location == "description.txt") {
            /* Array( in meta XML but erroneous!
                [0] => taxonID
                [1] => description
                [2] => furtherInformationURL
                [3] => Owner
                [4] => language
                [5] => CVterm
                [6] => format
                [7] => type
                [8] => agentID
                [9] => UsageTerms
                [10] => identifier
            )
            [0] => ff67df9f-4607-4728-82b7-1aeaf243ed95
            [1] => behaviour
            [2] => <p>The <a href="/glossary/song" title="In bioacoustics this term is used in two main sense: in the broadest sense it is applied to the deliberate acoustic output of animals (or a group of animals) in general, and in a more restricted sense it is applied to the acoustic output of a particular species or individual. [bib]17157[/bib]" class="lexicon-term">song</a> is produced at dusk and in the night. It is audible from about 1m and consists of 'tsp'-sounds, which are grouped into a series of one to four. Three to five such series in turm form a <a href="/glossary/phrase" title="See Echeme<br/>" class="lexicon-term">phrase</a> which has a total duration of 2-3 sec. and is seperatedf by a longer interval from the next one. Such a phrase can ber represented in the following way: 'tsptsp-tsptsptsp-tsptsp-tsp'. <a href="#ref1" title="Bellman H. A Field Guide to the Grasshoppers and Crickets of Britain and Northern Europe. William Collins & Sons; 1988.">[1]</a></p><br/><br/><br/><hr /><h3>References</h3><div class="references"><ol><li id="reference1"><a name="ref1" id="ref1"><span class="biblio-authors"><a href="/biblio?f[author]=625" rel="nofollow">Bellman H</a></span>. </a><a href="/content/field-guide-grasshoppers-and-crickets-britain-and-northern-europe"><span class="biblio-title" style="font-style: italic;">A Field Guide to the Grasshoppers and Crickets of Britain and Northern Europe</span></a>. William Collins & Sons; 1988.<span class="Z3988" title="ctx_ver=Z39.88-2004&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&rft.title=A+Field+Guide+to+the+Grasshoppers+and+Crickets+of+Britain+and+Northern+Europe&rft.issn=0-00-219852-5&rft.date=1988&rft.aulast=Bellman&rft.aufirst=Heiko&rft.pub=William+Collins+%26amp%3B+Sons"></span> </li><br/><br/></ol></div>
            [3] => 
            [4] => http://bio.acousti.ca/content/barbitistes-serricauda-1
            [5] => 
            [6] => BioAcoustica: Wildlife Sounds Database
            [7] => eng
            [8] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Behaviour
            [9] => text/html
            [10] => http://purl.org/dc/dcmitype/Text
            [11] => ac2a0544-7322-4cf7-8eb9-c6e88479669e
            [12] => //creativecommons.org/licenses/by-nc-sa/3.0/
            [13] => ac7e9359-8107-4b57-a5c2-8d0d0d5bc319#behaviour
            */
            $fields = array();
            $fields[0] = 'taxonID';
            $fields[1] = '';
            $fields[2] = 'description';
            $fields[3] = '';
            $fields[4] = 'furtherInformationURL';
            $fields[5] = "";
            $fields[6] = "Owner";
            $fields[7] = "language";
            $fields[8] = "CVterm";
            $fields[9] = "format";
            $fields[10] = "type";
            $fields[11] = "agentID";
            $fields[12] = "UsageTerms";
            $fields[13] = "identifier";
            $count = count($fields);
            // print_r($tbl); exit;
            $tbl->fields = array();
            $tbl->fields[0] = array('term' => 'http://rs.tdwg.org/dwc/terms/taxonID', 'default' => '');
            $tbl->fields[1] = array('term' => '', 'type' => '', 'default' => ''); 
            $tbl->fields[2] = array('term' => '	http://purl.org/dc/terms/description', 'type' => '', 'default' => ''); 
            $tbl->fields[3] = array('term' => '', 'type' => '', 'default' => ''); 
            $tbl->fields[4] = array('term' => 'http://rs.tdwg.org/ac/terms/furtherInformationURL', 'type' => '', 'default' => ''); 
            $tbl->fields[5] = array('term' => '', 'type' => '', 'default' => ''); 
            $tbl->fields[6] = array('term' => '	http://ns.adobe.com/xap/1.0/rights/Owner', 'type' => '', 'default' => ''); 
            $tbl->fields[7] = array('term' => '	http://purl.org/dc/terms/language', 'type' => '', 'default' => ''); 
            $tbl->fields[8] = array('term' => 'http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm', 'type' => '', 'default' => ''); 
            $tbl->fields[9] = array('term' => 'http://purl.org/dc/terms/format', 'type' => '', 'default' => ''); 
            $tbl->fields[10] = array('term' => 'http://purl.org/dc/terms/type', 'type' => '', 'default' => ''); 
            $tbl->fields[11] = array('term' => 'http://eol.org/schema/agent/agentID', 'type' => '', 'default' => ''); 
            $tbl->fields[12] = array('term' => 'http://ns.adobe.com/xap/1.0/rights/UsageTerms', 'type' => '', 'default' => ''); 
            $tbl->fields[13] = array('term' => 'http://purl.org/dc/terms/identifier', 'type' => '',  'default' => '');
        }
        // end customization ----------------------------------------------------------------------------------
        
        
        $do_ids = array(); //for validation, prevent duplicate identifiers
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            if    ($class == "vernacular")  $c = new \eol_schema\VernacularName();
            elseif($class == "agent")       $c = new \eol_schema\Agent();
            elseif($class == "reference")   $c = new \eol_schema\Reference();
            elseif($class == "taxon")       $c = new \eol_schema\Taxon();
            elseif($class == "document")    $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")  $c = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            else exit("\nUndefined class [$class]\n");

            $row = fgetcsv($file);

            /* good debug
            if($tbl->row_type == "http://eol.org/schema/media/Document" && $tbl->location == "description.txt") {
                print_r($fields); print_r($row); //good debug
            }
            */
            
            if($row) {
                // print_r($row);
                $row = self::clean_html($row);
                // print_r($row);
            }
            else continue;
            
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            // if($i > 2000) break; //debug only - process a subset first 2k
            
            if($i == 1 && !$fields) {
                $fields = $row;
                $count = count($fields);
                // print_r($fields); break; //debug
            }
            else { //main records

                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    // echo("\nWrong CSV format for this row.\n");
                    $this->debug['wrong csv'][$class]['identifier'][@$rec['identifier']] = '';
                    $this->debug['wrong csv 2'][$class][$csv_file][$count][count($values)] = '';
                    
                    /* good debug - when you want to get actual record values
                    if($tbl->row_type == "http://eol.org/schema/media/Document" && $tbl->location == "description.txt") {
                        echo "\nwill cont. [$tbl->row_type][$count][".count($values)."]";
                        print_r($fields); print_r($values);
                    }
                    */
                    
                    continue;
                }

                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    if($this->resource_id == 430) $rec[$field] = Functions::remove_this_last_char_from_str($values[$k], "|");
                    else
                    {
                        $rec[$field] = $values[$k];
                        
                        //================================================================== start customization ==================================================================
                        /* good debug
                        if($rec[$field] == "//creativecommons.org/licences/by-nc/3.0/") {
                            exit("\n[$field]\n");
                        }
                        */
                        if($field == "UsageTerms" && substr($rec[$field],0,21) == "//creativecommons.org") $rec[$field] = "http:".$rec[$field];
                        elseif($field == "UsageTerms" && $rec[$field] == "http://creativecommons.org/about/pdm") $rec[$field] = "http://creativecommons.org/licenses/publicdomain/";
                        elseif($field == "UsageTerms" && !$rec[$field]) $rec[$field] = "http://creativecommons.org/licences/by-nc/3.0/";

                        // accessURI
                        //================================================================== end customization ==================================================================
                        
                    }
                    $k++;
                }
                
                /* good debug
                if($class == 'agent') {
                    print_r($fields); print_r($rec); print_r($tbl); exit;
                }
                */
                //================================================================== start customization ==================================================================
                if($class == 'document') {
                    if(Functions::is_mediaYN(@$rec['type']) && !$rec['accessURI']) continue;
                    else
                    {

                    }
                }
                
                // ---------------------------- START putting furtherInformationURL from taxa to objects ----------------------------
                if($class == 'taxon') {
                    $this->special[$rec['taxonID']]['furtherInformationURL'] = @$rec['furtherInformationURL'];
                }
                /* use this if u want to put taxon furtherInfoURL in obj furtherInfoURL only if former is blank. Not used though.
                if($class == 'document') {
                    if(!@$rec['furtherInformationURL']) $rec['furtherInformationURL'] = @$this->special[$rec['taxonID']]['furtherInformationURL'];
                }
                */
                if($class == 'document') {
                    if($val = @$this->special[$rec['taxonID']]['furtherInformationURL']) $rec['furtherInformationURL'] = $val;
                }
                // ---------------------------- END putting furtherInformationURL from taxa to objects ----------------------------
                
                
                
                //================================================================== end customization ==================================================================
                
                
                
                
                //start process record =============================================================================================
                if($class == 'document') {
                    if($rec['taxonID'] && @$rec['accessURI']) {
                        if(!Functions::valid_uri_url($rec['accessURI'])) continue;
                        if(!Functions::valid_uri_url(@$rec['thumbnailURL'])) $rec['thumbnailURL'] = "";
                    }
                    $do_id = $rec['identifier'];
                    if(in_array($do_id, $do_ids)) {
                        // exit("\nduplicate do_id [$do_id]\n"); //debug
                        continue;
                    }
                    else $do_ids[] = $do_id;
                }
                
                // print_r($tbl); exit;
                foreach($tbl->fields as $f) {
                    $field = pathinfo($f['term'], PATHINFO_FILENAME);
                    
                    // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName" or "wgs84_pos#lat"
                    $parts = explode("#", $field);
                    if($parts[0]) $field = $parts[0];
                    if(@$parts[1]) $field = $parts[1];
                    
                    if(in_array($class, array("agent", "reference")) && $field == "taxonID") {}
                    elseif(in_array($class, array("agent")) && $field == "term_givenName") {}
                    else $c->$field = $rec[$field]; //normal operation
                }
                //end process record =============================================================================================

                // print_r($rec); exit;
                
            } //main records
            $this->archive_builder->write_object_to_file($c);
            
            // if($i > 100000) break; //debug
            
        } //main loop
        fclose($file);
    }
    
    // private function valid_uri_url($str)
    // {
    //     if(substr($str,0,7) == "http://") return true;
    //     elseif(substr($str,0,8) == "https://") return true;
    //     return false;
    // }
    
    /* was not used
    private function clean_html($html)
    {
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        return Functions::remove_whitespace($html);
    }
    function start_fix_supplied_archive_by_partner()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*25, 'cache' => 1)); //expires in 25 days 
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        print_r($paths);
        self::process_extension($archive_path);
        recursive_rmdir($temp_dir);
    }
    */
}
?>
