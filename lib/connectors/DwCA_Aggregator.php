<?php
namespace php_active_record;
/* connector: [aggregate_resources.php] - first client
Processes any DwCA archive file.
Using the parentNameUsageID, generates a new DwCA with a new taxon column: http://rs.tdwg.org/dwc/terms/higherClassification
User Warning: Undefined property `rights` on eol_schema\Taxon as defined by `http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 168
*/
class DwCA_Aggregator
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        /* un-comment if it will cause probs to other connectors
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        */
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
                                  "http://eol.org/schema/reference/reference"       => "reference"
                                  );

                                  /*
                                  [1] => http://rs.gbif.org/terms/1.0/speciesprofile
                                  [6] => http://rs.gbif.org/terms/1.0/typesandspecimen
                                  [7] => http://rs.gbif.org/terms/1.0/distribution
                                  "http://eol.org/schema/association"               => "association"
                                  */
    
        if(@$this->resource_id == 24) {
            $this->taxon_ids = array();
        }
        
        $this->public_domains = array("http://creativecommons.org/licenses/publicdomain/", "https://creativecommons.org/share-your-work/public-domain/", "https://creativecommons.org/share-your-work/public-domain/cc0/");
    }
    function count_records_in_dwca()
    {
        if(!($info = self::start())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $totals = array();
        foreach($index as $row_type) {
            $count = self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type], false); //3rd param = false means count only, no archive will be generated
            $totals[$row_type] = $count;
        }
        print_r($totals);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }
    private function start($dwca_file = false, $download_options = array('timeout' => 172800, 'expire_seconds' => false)) //probably default expires in a month 60*60*24*30. Not false.
    {
        if($dwca_file) $this->dwca_file = $dwca_file;
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths);
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/'
        );
        */
        
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    function combine_DwCAs($langs)
    {
        foreach($langs as $lang) {
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$lang.'.tar.gz';
            $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/media/document');
            self::convert_archive($preferred_rowtypes, $dwca_file);
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function convert_archive($preferred_rowtypes = false, $dwca_file)
    {   /* param $preferred_rowtypes is the option to include-only those row_types you want on your final DwCA.*/
        echo "\nConverting archive to EOL DwCA...\n";
        $info = self::start($dwca_file, array('timeout' => 172800, 'expire_seconds' => 0)); //1 day expire -> 60*60*24*1
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        /* e.g. $index -> these are the row_types
        Array
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/vernacularname
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
            [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        // print_r($index); exit; //good debug to see the all-lower case URIs
        foreach($index as $row_type) {
            /* ----------customized start------------ */
            if($this->resource_id == 'wikipedia_combined_languages') break; //all extensions will be processed elsewhere.
            /* ----------customized end-------------- */
            /* not used - copied template
            if($preferred_rowtypes) {
                if(!in_array($row_type, $preferred_rowtypes)) continue;
            }
            if(@$this->extensions[$row_type]) { //process only defined row_types
                // if(@$this->extensions[$row_type] == 'document') continue; //debug only
                echo "\nprocessing...: [$row_type]: ".@$this->extensions[$row_type]."...\n";
                self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
            }
            else echo "\nun-processed: [$row_type]: ".@$this->extensions[$row_type]."\n";
            */
        }
        
        // /* ================================= start of customization =================================
        if($this->resource_id == 'wikipedia_combined_languages') {
            // require_library('connectors/USDAPlants2019');
            // $func = new USDAPlants2019($this->archive_builder, $this->resource_id);
            // $func->start($info);
            
            $tables = $info['harvester']->tables;
            // print_r($tables); exit;
            /*Array(
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://eol.org/schema/media/document
            )*/
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
            self::process_table($tables['http://eol.org/schema/media/document'][0], 'document');
        }
        // ================================= end of customization ================================= */ 
        
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
        if($this->debug) print_r($this->debug);
    }
    private function process_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocessing [$what]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => Q140
                [http://purl.org/dc/terms/source] => http://ta.wikipedia.org/w/index.php?title=%E0%AE%9A%E0%AE%BF%E0%AE%99%E0%AF%8D%E0%AE%95%E0%AE%AE%E0%AF%8D&oldid=2702618
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => Q127960
                [http://rs.tdwg.org/dwc/terms/scientificName] => Panthera leo
                [http://rs.tdwg.org/dwc/terms/taxonRank] => species
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Carl Linnaeus, 1758
            )*/
            $uris = array_keys($rec);
            if($what == "taxon")           $o = new \eol_schema\Taxon();
            elseif($what == "document")    $o = new \eol_schema\MediaResource();
            
            $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($what == "taxon") {
                if(stripos($rec['http://purl.org/dc/terms/source'], "wikipedia.org") !== false) $rec['http://purl.org/dc/terms/source'] = 'https://www.wikidata.org/wiki/'.$taxon_id; //string is found
                if(!isset($this->taxon_ids[$taxon_id])) {
                    $this->taxon_ids[$taxon_id] = '';
                }
                else continue;
            }
            
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 2) break; //debug only
        }
    }
    
    function convert_archive_normalized() //this same as above two, but this removes taxa that don't have objects. Only taxa with objects will remain in taxon.tab.
    {
        echo "\ndoing this: convert_archive_normalized()\n";
        $info = self::start(false, array('timeout' => 172800, 'expire_seconds' => 60*60*24)); //1 day
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        if($records = $harvester->process_row_type('http://eol.org/schema/media/Document'))
        {
            $taxon_ids_with_objects = self::build_taxonIDs_with_objects_array($records);        echo "\n1 of 3\n";
            $records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');      echo "\n2 of 3\n";
            $records = self::remove_taxa_without_objects($records, $taxon_ids_with_objects);    echo "\n3 of 3\n";
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    if($this->extensions[$row_type] == "taxon") self::process_fields($records, $this->extensions[$row_type]);
                    else                                        self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
            }
            $this->archive_builder->finalize(TRUE);
        }
        else {
            echo "\nNo data objects for this resource [$this->resource_id].\n";
            recursive_rmdir($this->path_to_archive_directory);
        }
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }
    //next 2 private functions are for convert_archive_customize_tab()
    private function can_customize($record, $fields)
    {
        foreach($fields as $field) {
            if(!isset($record[$field])) return false;
        }
        return true;
    }
    private function customize_tab($records, $jira, $rowtype = "")
    {
        //------------------------------------------------------------customization start
        if($jira == "DATA-1779") {}
        //------------------------------------------------------------customization end
        echo "\n start taxa count: ".count($records);
        $i = -1;
        foreach($records as $rec) {
            $i++;
            // print_r($rec); exit;
            /*Array( e.g. a media extension
                [http://purl.org/dc/terms/identifier] => 3603194
                [http://rs.tdwg.org/dwc/terms/taxonID] => dc35ea52861f3d5a5be14a4bdd2832c3
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/StillImage
                [http://rs.tdwg.org/audubon_core/subtype] => 
                [http://purl.org/dc/terms/format] => image/jpeg
                [http://purl.org/dc/terms/description] => This image revealed the presence of both the <i>human T-cell leukemia type-1 virus</i> (HTLV-1), (also known as the <i>human T lymphotropic virus type-1 virus</i>), and the <i>human immunodeficiency virus</i> (HIV).<br>Created:
                [http://rs.tdwg.org/ac/terms/accessURI] => https://editors.eol.org/other_files/EOL_media/94/3603194.jpg
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://phil.cdc.gov/phil/home.asp
                [http://purl.org/dc/terms/language] => en
                [http://ns.adobe.com/xap/1.0/Rating] => 2.5
                [http://purl.org/dc/terms/audience] => Expert users; General public
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/publicdomain/
                [http://purl.org/dc/terms/rights] => <B>None</b> - This image is in the public domain and thus free of any copyright restrictions. As a matter of courtesy we request that the content provider be credited and notified in any public or private usage of this image.
                [http://ns.adobe.com/xap/1.0/rights/Owner] => Public Health Image Library
                [http://eol.org/schema/agent/agentID] => 33b5e131211fb3858b3ddf9a6e1c605a
            )*/
            if($jira == "DATA-1779") { //if license is 'public domain', make 'Owner' field blank.
                $license = (string) $rec["http://ns.adobe.com/xap/1.0/rights/UsageTerms"];
                if(in_array($license, $this->public_domains)) {
                    // echo "\nfound criteria [".$records[$i]['http://ns.adobe.com/xap/1.0/rights/Owner']."]";
                    $records[$i]['http://ns.adobe.com/xap/1.0/rights/Owner'] = "";
                    // print_r($records[$i]); exit;
                }
            }
            if($jira == "DATA-1799") { //remove taxon entry when taxonID is missing
                                       //remove media entry when taxonID is missing
                $taxonID = (string) trim($rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
                if(!$taxonID) {
                    /* a diff. case when you want to delete the entire record altogether
                    $records[$i] = NULL;
                    */
                    if($rowtype == "http://rs.tdwg.org/dwc/terms/Taxon") {
                        $this->taxonID_to_use = md5(json_encode($rec))."_eolx";
                        $records[$i]['http://rs.tdwg.org/dwc/terms/taxonID'] = $this->taxonID_to_use;
                    }
                    elseif($rowtype == "http://eol.org/schema/media/Document") {
                        $records[$i]['http://rs.tdwg.org/dwc/terms/taxonID'] = $this->taxonID_to_use;
                    }
                }
            }
            
        }
        $records = array_filter($records); //remove null arrays
        $records = array_values($records); //reindex key
        echo "\n end taxa count: ".count($records);
        return $records;
    }
    function convert_archive_customize_tab($options) //first clients are DATA-1779, DATA-1799. This will customize DwCA extension(s).
    {
        echo "\ndoing this: convert_archive_customize_tab()\n";
        $info = self::start(); $temp_dir = $info['temp_dir']; $harvester = $info['harvester']; $tables = $info['tables']; $index = $info['index'];
        
        foreach($options['row_types'] as $rowtype) { //process here those extensions that need customization
            $records = $harvester->process_row_type($rowtype);
            $records = self::customize_tab($records, $options['Jira'], $rowtype);
            self::process_fields($records, $this->extensions[strtolower($rowtype)]);
        }

        $options['row_types'] = array_map('strtolower', $options['row_types']); //important step so to have similar lower-case strings
        /* print_r($index); print_r($options['row_types']); exit; */ //check if strings have same case, before to proceed with comparing.

        foreach($index as $row_type) { //process remaining row_types
            if(@$this->extensions[$row_type]) { //process only defined row_types
                if(!in_array($row_type, $options['row_types'])) self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
            }
        }
        $this->archive_builder->finalize(TRUE);
        
        recursive_rmdir($temp_dir); echo ("\n temporary directory removed: " . $temp_dir); // remove temp dir
        if($this->debug) print_r($this->debug);
    }
    private function build_id_name_array($records)
    {
        foreach($records as $rec) {
            // [http://rs.tdwg.org/dwc/terms/taxonID] => 6de0dc42e8f4fc2610cb4287a4505764
            // [http://rs.tdwg.org/dwc/terms/scientificName] => Accipiter cirrocephalus rosselianus Mayr, 1940
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $this->id_name[$taxon_id]['scientificName'] = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
            $this->id_name[$taxon_id]['parentNameUsageID'] = (string) $rec["http://rs.tdwg.org/dwc/terms/parentNameUsageID"];
        }
    }
    private function generate_higherClassification_field($records)
    {   /* e.g. $rec
        Array
            [http://rs.tdwg.org/dwc/terms/taxonID] => 5e2712849c197671c260f53809836273
            [http://rs.tdwg.org/dwc/terms/scientificName] => Passerina leclancherii leclancherii Lafresnaye, 1840
            [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 49fc924007e33cc43908fed677d5499a
        */
        $i = 0;
        foreach($records as $rec) {
            $higherClassification = self::get_higherClassification($rec);
            $records[$i]["higherClassification"] = $higherClassification; //assign value to main $records -> UNCOMMENT in real operation
            $i++;
        }
        return $records;
    }
    private function get_higherClassification($rek)
    {
        $parent_id = $rek['http://rs.tdwg.org/dwc/terms/parentNameUsageID'];
        $str = "";
        while($parent_id) {
            if($parent_id) {
                $str .= Functions::canonical_form(trim(@$this->id_name[$parent_id]['scientificName']))."|";
                $parent_id = @$this->id_name[$parent_id]['parentNameUsageID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1);
        // echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr);
        // echo "\n new: [$str]\n";
        return $str;
    }
    private function can_compute_higherClassification($records)
    {
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/taxonID"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/scientificName"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/parentNameUsageID"])) return false;
        return true;
    }
    private function valid_uri_url($str)
    {
        $str = str_ireplace('http', 'http', $str); //bec some have something like Http://...
        if(substr($str,0,7) == "http://") return true;
        elseif(substr($str,0,8) == "https://") return true;
        return false;
    }
    //ends here 
    
    /* not used at the moment...
    private function create_taxa($taxa)
    {
        foreach($taxa as $t)
        {   
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $t['AphiaID'];
            $taxon->scientificName  = trim($t['scientificname'] . " " . $t['authority']);
            $taxon->taxonRank       = $t['rank'];
            $taxon->taxonomicStatus = $t['status'];
            $taxon->source          = $this->taxon_page . $t['AphiaID'];
            $taxon->parentNameUsageID = $t['parent_id'];
            $taxon->acceptedNameUsageID     = $t['valid_AphiaID'];
            $taxon->bibliographicCitation   = $t['citation'];
            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
    }
    */
    
    //=====================================================================================================================
    //start functions for the interface tool "genHigherClass"
    //=====================================================================================================================
    
    function tool_generate_higherClassification($file)
    {
        if($records = self::create_records_array($file))
        {
            self::build_id_name_array($records);                                //echo "\n1 of 3\n";
            $records = self::generate_higherClassification_field($records);     //echo "\n2 of 3\n";
            $fields = self::normalize_fields($records[0]);

            //start write to file
            // $file = str_replace(".", "_higherClassification.", $file);
            if(!($f = Functions::file_open($file, "w"))) return;
            fwrite($f, implode("\t", $fields)."\n");
            foreach($records as $rec) fwrite($f, implode("\t", $rec)."\n");
            fclose($f);
            // echo "\n3 of 3\n";
            return true;
        }
        else return false;
    }
    
    private function create_records_array($file)
    {
        $records = array();
        $i = 0;
        foreach(new FileIterator($file) as $line => $row)
        {
            $i++;
            if($i == 1)
            {
                $fields = explode("\t", $row);
                $k = 0;
                foreach($fields as $field) //replace it with the long field URI
                {
                    if($field == "taxonID") $fields[$k] = "http://rs.tdwg.org/dwc/terms/taxonID";
                    elseif($field == "scientificName") $fields[$k] = "http://rs.tdwg.org/dwc/terms/scientificName";
                    elseif($field == "parentNameUsageID") $fields[$k] = "http://rs.tdwg.org/dwc/terms/parentNameUsageID";
                    $k++;
                }
            }
            else
            {
                $rec = array();
                $cols = explode("\t", $row);
                $k = 0;
                foreach($fields as $field)
                {
                    $rec[$field] = @$cols[$k];
                    $k++;
                }
                if($rec)
                {
                    if($i == 3) //can check this early if we can compute for higherClassification
                    {
                        if(!self::can_compute_higherClassification($records)) return false;
                    }
                    $records[] = $rec;
                }
            }
        }
        return $records;
    }
    
    private function normalize_fields($arr)
    {
        $fields = array_keys($arr);
        $k = 0;
        foreach($fields as $field)
        {
            $fields[$k] = pathinfo($field, PATHINFO_FILENAME);
            $k++;
        }
        return $fields;
    }
    //=====================================================================================================================
    //end functions for the interface tool "genHigherClass"
    //=====================================================================================================================

    // these 2 functions used in convert_archive_normalized()
    private function build_taxonIDs_with_objects_array($records)
    {
        $taxon_ids = array();
        foreach($records as $rec) {
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon_ids[$taxon_id] = '';
        }
        return array_keys($taxon_ids);
    }
    private function remove_taxa_without_objects($records, $taxon_ids_with_objects)
    {
        echo "\n start taxa count: ".count($records);
        $i = -1;
        foreach($records as $rec) {
            $i++;
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon_status = (string) @$rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            if(!in_array($taxon_id, $taxon_ids_with_objects) && !in_array($taxon_status, array('synonym'))) $records[$i] = null;
        }
        $records = array_filter($records); //remove null arrays
        $records = array_values($records); //reindex key
        echo "\n end taxa count: ".count($records);
        return $records;
    }

    //=====================================================================================================================
    //start OTHER functions
    //=====================================================================================================================
    function get_uri_value($raw, $uri_values) //$raw e.g. "Philippines" ---- good func but not yet used, soon...
    {
        if($uri = @$uri_values[$raw]) return $uri;
        else {
            switch ($raw) { //put here customized mapping
                case "United States of America":    return "http://www.wikidata.org/entity/Q30";
                case "Port of Entry":               return false; //"DO NOT USE"
            }
        }
        return false;
    }
    
    //=====================================================================================================================
    //end OTHER functions
    //=====================================================================================================================

}
?>