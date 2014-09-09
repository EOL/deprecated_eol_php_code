<?php
namespace php_active_record;
/* connector: [42] - This is now scheduled as a cron task.
We created a script for FishBase and turned it over to them. This script is now installed in their system.
The script reads their MS Acccess database and creates tab-delimited text files.
They then zip these files and host it in their server.
The connector in this page then reads this zip file, extracts, assembles the information and generate the EOL DWCA.
FishBase contacts are: Skit Barile <j.barile@fin.ph> and Emily Capuli <e.capuli@fin.ph>
*/
class FishBaseArchiveAPI
{
    public function __construct($test_run = false, $folder)
    {
        $this->test_run = $test_run;
        // $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase_in_folder.zip";
        // $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase_not_in_folder.zip";
        // $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase.zip";
        $this->fishbase_data = "http://www.fishbase.us/FB_data_for_EOL/fishbase.zip";
        if($this->test_run) $this->fishbase_data = "http://dl.dropbox.com/u/7597512/FishBase/fishbase_not_in_folder.zip";
        $this->text_path = array();
        $this->TEMP_FILE_PATH = "";

        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxa_ids             = array();
        $this->taxa_reference_ids   = array(); // $this->taxa_reference_ids[taxon_id] = reference_ids
        $this->object_reference_ids = array();
        $this->object_agent_ids     = array();
        $this->reference_ids        = array();
        $this->agent_ids            = array();
    }

    function get_all_taxa($resource_id)
    {
        self::prepare_data();
        // remove tmp dir
        $this->TEMP_FILE_PATH = str_ireplace("/fishbase", "", $this->TEMP_FILE_PATH);
        // if($this->TEMP_FILE_PATH) shell_exec("rm -fr $this->TEMP_FILE_PATH");
        recursive_rmdir($this->TEMP_FILE_PATH); // debug uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
        if($this->test_run) return $all_taxa; //used in testing
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->fishbase_data, array('timeout' => 172800)))
        {
            $temp_file_path = $this->TEMP_FILE_PATH . "/fishbase.zip";
            $TMP = fopen($temp_file_path, "w");
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("tar -xzf $temp_file_path -C $this->TEMP_FILE_PATH");

            if(!file_exists($this->TEMP_FILE_PATH . "/taxon.txt")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/taxon.txt")) return;
            }

            $this->text_path['TAXON_PATH']                       = $this->TEMP_FILE_PATH . "/taxon.txt";
            $this->text_path['TAXON_COMNAMES_PATH']              = $this->TEMP_FILE_PATH . "/taxon_comnames.txt";
            $this->text_path['TAXON_DATAOBJECT_PATH']            = $this->TEMP_FILE_PATH . "/taxon_dataobject.txt";
            $this->text_path['TAXON_DATAOBJECT_AGENT_PATH']      = $this->TEMP_FILE_PATH . "/taxon_dataobject_agent.txt";
            $this->text_path['TAXON_DATAOBJECT_REFERENCE_PATH']  = $this->TEMP_FILE_PATH . "/taxon_dataobject_reference.txt";
            $this->text_path['TAXON_REFERENCES_PATH']            = $this->TEMP_FILE_PATH . "/taxon_references.txt";
            $this->text_path['TAXON_SYNONYMS_PATH']              = $this->TEMP_FILE_PATH . "/taxon_synonyms.txt";
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }
    
    function prepare_data()
    {
        self::load_zip_contents();
        self::process_taxa_references();        echo "\n taxa references -- DONE";
        self::process_taxa();                   echo "\n taxa -- DONE";
        self::process_taxa_comnames();          echo "\n common names -- DONE";
        self::process_taxa_synonyms();          echo "\n synonyms -- DONE";
        self::process_taxa_object_references(); echo "\n dataObject references -- DONE";
        self::process_taxa_object_agents();     echo "\n agents -- DONE";
        self::process_taxa_objects();           echo "\n dataObjects -- DONE";
        $this->archive_builder->finalize(true);
    }

    private function process_taxa_synonyms()
    {
        /*
        [2] => Array
                (
                    [0] => Array
                        (
                            [synonym] => Clupea alba Yarrell, 1829
                            [relationship] => synonym
                        )
                    [1] => Array
                        (
                            [synonym] => Clupea atlanticus Schnakenbeck, 1931
                            [relationship] => synonym
                        )
        */
        $fields = array("synonym", "author", "relationship", "int_id", "timestamp", "autoctr");
        $taxon_synonyms = self::make_array($this->text_path['TAXON_SYNONYMS_PATH'], $fields, "int_id", array(1,4,5));
        foreach($taxon_synonyms as $taxon_id => $synonyms)
        {
            $taxon_id = str_replace("\N", "", $taxon_id);
            if(!$taxon_id = trim($taxon_id)) continue;
            foreach($synonyms as $s)
            {
                foreach($s as $key => $value) $s[$key] = str_replace("\N", "", $value);
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID             = md5($s['synonym']);
                $taxon->scientificName      = utf8_encode($s['synonym']);
                $taxon->acceptedNameUsageID = $this->taxa_ids[$taxon_id];
                if($s['relationship'] == 'valid name') $s['relationship'] = 'synonym';
                if(strtolower($s['relationship']) != 'xxx') $taxon->taxonomicStatus = $s['relationship'];
                if(!isset($this->synonym_ids[$taxon->taxonID]))
                {
                    $this->synonym_ids[$taxon->taxonID] = '';
                    $this->archive_builder->write_object_to_file($taxon);
                }
            }
        }
    }
    
    private function process_taxa_object_agents()
    {
        /*
        [206] => Array
            (
                [0] => Array
                    (
                        [agent] => Armi G. Torres
                        [homepage] => 
                        [logoURL] => 
                        [role] => recorder
                    )
        */
        $fields = array("agent", "homepage", "logoURL", "role", "int_do_id", "timestamp");
        $taxon_dataobject_agent = self::make_array($this->text_path['TAXON_DATAOBJECT_AGENT_PATH'], $fields, "int_do_id", array(5));

        foreach($taxon_dataobject_agent as $do_id => $agents) //do_id is int_do_id in FB text file
        {
            $agent_ids = array();
            foreach($agents as $a)
            {
                if(!$a['agent']) continue;
                $r = new \eol_schema\Agent();
                $r->term_name       = $a['agent'];
                $r->agentRole       = $a['role'];
                $r->identifier      = md5("$r->term_name|$r->agentRole");
                $r->term_homepage   = $a['homepage'];
                $agent_ids[] = $r->identifier;
                if(!isset($this->agent_ids[$r->identifier]))
                {
                   $this->agent_ids[$r->identifier] = '';
                   $this->archive_builder->write_object_to_file($r);
                }
            }
            $this->object_agent_ids[$do_id] = $agent_ids;
        }
    }

    private function process_taxa_object_references()
    {
        $fields = array("reference", "bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn", "int_do_id");
        $taxon_dataobject_reference = self::make_array($this->text_path['TAXON_DATAOBJECT_REFERENCE_PATH'], $fields, "int_do_id", array(1,2,3,4,5,7,8,9,10,12));
        /*
        [206] => Array
             (
                 [0] => Array
                     (
                         [reference] => IUCN 2006 2006 IUCN red list of threatened species. www.iucnredlist.org. Downloaded July 2006.
                         [isbn] => 
                         [url] => http://www.fishbase.org/references/FBRefSummary.php?id=57073
                     )
        */
        foreach($taxon_dataobject_reference as $do_id => $refs) //do_id is int_do_id in FB text file
        {
            $reference_ids = self::create_references($refs);
            $this->object_reference_ids[$do_id] = $reference_ids;
        }
    }
    
    private function create_references($refs)
    {
        $reference_ids = array();
        foreach($refs as $ref)
        {
            foreach($ref as $key => $value) $ref[$key] = str_replace("\N", "", $value);
            if(!Functions::is_utf8($ref['reference'])) continue;
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref['reference'];
            $r->identifier = md5($r->full_reference);
            $r->uri = $ref['url'];
            $reference_ids[] = $r->identifier;
            if(!isset($this->reference_ids[$r->identifier]))
            {
               $this->reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_unique($reference_ids);
    }
    
    private function process_taxa_objects()
    {
        /*
        [dc_identifier] => FB-pic-2-13870
        [dataType] => http://purl.org/dc/dcmitype/StillImage
        [mimeType] => image/gif
        [dc_title] => 
        [license] => http://creativecommons.org/licenses/by-nc/3.0/
        [dc_rights] => Image can be freely copied and altered but may be used only for non-commercial purposes. Original author and source must be properly acknowledged
        [dcterms_bibliographicCitation] => \N
        [dc_source] => http://www.fishbase.org/summary/SpeciesSummary.php?id=2
        [subject] => 
        [dc_description] => 
        [mediaURL] => http://www.fishbase.us/images/species/Ornil_u0.gif
        [thumbnailURL] => http://www.fishbase.us/images/thumbnails/gif/tn_Ornil_u0.gif
        [location] => 
        [int_do_id] => 2
        [dc_rightsHolder] => WorldFish Center - FishBase
        */
        $fields = array("TaxonID", "dc_identifier", "dataType", "mimeType", "dcterms_created", "dcterms_modified", "dc_title", "dc_language", "license", "dc_rights", "dcterms_bibliographicCitation", "dc_source", "subject", "dc_description", "mediaURL", "thumbnailURL", "location", "xml_lang", "geo_point", "lat", "long", "alt", "timestamp", "int_id", "int_do_id", "dc_rightsHolder");
        $taxa_objects = self::make_array($this->text_path['TAXON_DATAOBJECT_PATH'], $fields, "int_id", array(0,4,5,7,17,18,19,20,21,22));
        foreach($taxa_objects as $taxon_id => $objects) //taxon_id is int_id in FB text file
        {
            foreach($objects as $o)
            {
                foreach($o as $key => $value) $o[$key] = str_replace("\N", "", $value);
                $mr = new \eol_schema\MediaResource();
                $mr->taxonID        = $this->taxa_ids[$taxon_id];
                $mr->identifier     = $o['dc_identifier'];
                $mr->type           = $o['dataType'];
                $mr->language       = 'en';
                $mr->format         = $o['mimeType'];
                if(substr($o['dc_source'], 0, 4) == "http") $mr->furtherInformationURL = $o['dc_source'];
                $mr->accessURI      = $o['mediaURL'];
                $mr->thumbnailURL   = $o['thumbnailURL'];
                $mr->CVterm         = $o['subject'];
                $mr->Owner          = $o['dc_rightsHolder'];
                $mr->rights         = $o['dc_rights'];
                $mr->title          = $o['dc_title'];
                $mr->UsageTerms     = $o['license'];
                $mr->audience       = 'Everyone';
                $mr->description    = utf8_encode($o['dc_description']);
                if(!Functions::is_utf8($mr->description)) continue;
                $mr->LocationCreated = $o['location'];
                $mr->bibliographicCitation = $o['dcterms_bibliographicCitation'];
                if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
                if($agent_ids     =     @$this->object_agent_ids[$o['int_do_id']])  $mr->agentID = implode("; ", $agent_ids);
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }
    
    private function process_taxa_comnames()
    {
        /*
        [1] => Array --- this is int_id
                (
                    [0] => Array
                        (
                            [commonName] => Akpafiatsi
                            [xml_lang] => ee
                        )
        */
        $fields = array("commonName", "xml_lang", "int_id");
        $taxon_comnames = self::make_array($this->text_path['TAXON_COMNAMES_PATH'], $fields, "int_id");
        foreach($taxon_comnames as $taxon_id => $names) //taxon_id is int_id in FB text file
        {
            if(!$taxon_id = trim($taxon_id)) continue;
            foreach($names as $name)
            {
                foreach($name as $key => $value) $name[$key] = str_replace("\N", "", $value);
                if(!Functions::is_utf8($name['commonName'])) continue;
                $v = new \eol_schema\VernacularName();
                $v->taxonID         = $this->taxa_ids[$taxon_id];
                $v->vernacularName  = trim($name['commonName']);
                $v->language        = $name['xml_lang'];
                $this->archive_builder->write_object_to_file($v);
            }
        }
    }
    
    private function process_taxa_references()
    {
        /*
        [3] => Array
                (
                    [0] => Array
                        (
                            [reference] => Russell, F.S.0 The eggs and planktonic stages of British marine fishes. Academic Press, London, UK. 524 p.
                            [isbn] => 
                            [url] => http://www.fishbase.org/references/FBRefSummary.php?id=37&speccode=69
                        )
                    [1] => Array
                        (
                            [reference] => Greenstreet, S.P.R.0 Estimation of the daily consumption of food by fish in the North Sea in each quarter of the year. Scott. Fish. Res. Rep. 55:1-16, plus tables.
                            [isbn] => 0 7480 51414
                            [url] => http://www.fishbase.org/references/FBRefSummary.php?id=42340&speccode=69
                        )
        */
        $fields = array("reference", "bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn", "int_id", "timestamp", "autoctr");
        $taxon_references = self::make_array($this->text_path['TAXON_REFERENCES_PATH'], $fields, "int_id", array(1,2,3,4,5,7,8,9,10,12,14,15));
        foreach($taxon_references as $taxon_id => $refs) //taxon_id is int_id in FB text file
        {
            $reference_ids = self::create_references($refs);
            $this->taxa_reference_ids[$taxon_id] = $reference_ids;
        }
    }
    
    private function process_taxa()
    {
        $fields = array("TaxonID", "dc_identifier", "dc_source", "dwc_Kingdom", "dwc_Phylum", "dwc_Class", "dwc_Order", "dwc_Family", "dwc_Genus", "dwc_ScientificName", "dcterms_created", "dcterms_modified", "int_id", "ProviderID");
        $taxa = self::make_array($this->text_path['TAXON_PATH'], $fields, "", array(0,10,11,13));
        if($taxa === false) return false;
        /*
        [dc_identifier] => FB-63276
        [dc_source] => http://www.fishbase.org/summary/speciessummary.php?id=63276
        [dwc_Kingdom] => Animalia
        [dwc_Phylum] => Chordata
        [dwc_Class] => Actinopterygii
        [dwc_Order] => Cypriniformes
        [dwc_Family] => Balitoridae
        [dwc_Genus] => Barbatula
        [dwc_ScientificName] => Barbatula bergamensis Erk&#39;Akan, Nalbant & ?zeren, 2007
        [int_id] => 10 ---> this is the id that binds FB text files
        */
        foreach($taxa as $t)
        {
            $this->taxa_ids[$t['int_id']] = $t['dc_identifier'];
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $t['dc_identifier'];
            $taxon->scientificName  = utf8_encode($t['dwc_ScientificName']);
            $taxon->kingdom         = $t['dwc_Kingdom'];
            $taxon->phylum          = $t['dwc_Phylum'];
            $taxon->class           = $t['dwc_Class'];
            $taxon->order           = $t['dwc_Order'];
            $taxon->family          = $t['dwc_Family'];
            $taxon->genus           = $t['dwc_Genus'];
            $taxon->furtherInformationURL = $t['dc_source'];
            if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
            $this->archive_builder->write_object_to_file($taxon);
        }
    }

    function make_array($filename, $fields, $index_key="", $excluded_fields=array(), $separator="\t")
    {
        /*
        $excluded_fields can be array of fieldnames e.g. array("taxonID", "scientificName");
        or can be array of index values of the fields array e.g. array("0", "1", "3")
        */
        $data = array();
        $included_fields = array();
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $values = explode($separator, $line);
                $i = 0;
                $temp = array();
                $continue_save = false;
                if(!$fields) $fields = array_map('trim', $values);
                foreach($fields as $field)
                {
                    if(is_int(@$excluded_fields[0])) $compare = $i;
                    else                             $compare = $field;
                    if(!in_array($compare, $excluded_fields))
                    {
                        $temp[$field] = trim(@$values[$i]);
                        $included_fields[$field] = 1;
                        if($temp[$field] != "") $continue_save = true; // as long as there is a single field with value then the row will be saved
                    }
                    $i++;
                }
                if($continue_save) $data[] = $temp;
            }
        }
        $included_fields = array_keys($included_fields);
        if($index_key)
        {
            $included_fields = array_unique($included_fields);
            return self::assign_key_to_table($data, $index_key, $included_fields);
        }
        else return $data;
    }

    function assign_key_to_table($table, $index_key, $included_fields)
    {
        $data = array();
        $included_fields = array_diff($included_fields, array($index_key));
        foreach($table as $record)
        {
            $index_value = $record["$index_key"];
            $temp = array();
            foreach($included_fields as $field) $temp[$field] = $record[$field];
            $data[$index_value][] = $temp;
        }
        return $data;
    }

    function get_references($references)
    {
        // might need or not need this...
        $ref = utf8_encode($reference['reference']);
        if(Functions::is_utf8($ref)) $refs[] = array("url" => $reference['url'], "fullReference" => Functions::import_decode($ref));
    }

    function get_common_names($names)
    {
        // might need or not need this...
        $common = utf8_encode($name['commonName']);
        if(Functions::is_utf8($common)) $arr_names[] = array("name" => Functions::import_decode($common), "language" => $name['xml_lang']);
    }

}
?>