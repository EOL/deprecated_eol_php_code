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
        $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase.zip";
        // $this->fishbase_data = "http://www.fishbase.us/FB_data_for_EOL/fishbase.zip";
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
        
        $this->uri_mappings_spreadsheet = "http://localhost/~eolit/cp/FishBase/URI_mappings.xlsx";
        $this->uri_mappings_spreadsheet = "http://localhost/~eolit/cp/FishBase/fishbase mappings.xlsx";
        
    }

    function get_all_taxa($resource_id)
    {
        $this->uris = self::get_uris();
        // print_r($this->uris); exit;
        
        
        /*
        $str = "bathydemersal; marine; depth range 549 - 1202 m (Ref. 97189)";
        $str = "benthopelagic; potamodromous (Ref. 51243); freshwater; brackish; pH range: 7.0 - 7.5; dH range: 15; depth range 1 - ? m (Ref. 9696)";
        $str = "depth range 220 - 457 m, usually 274 - 402 m";
        $str = "reef-associated; non-migratory; marine; depth range 6 - 80 m (Ref. 9334), usually 6 - 20 m (Ref. 6852)";
        $str = "demersal; freshwater; pH range: 6.6 - 36,751.0; depth range 0 - 1 m (Ref. 58018)";
        
        $parts = self::get_description_parts($str, false); 
        print_r($parts); 
        $a = self::process_habitat_data($parts);
        print_r($a);
        exit;
        */
        
        /*
        $str = "33.7 cm SL (male/unsexed; (Ref. 93606)); max. reported age: 33 years (Ref. 93630)";
        $str = "69.0 cm TL (male/unsexed; (Ref. 5578))";
        $str = "4.7 cm SL (male/unsexed; (Ref. 4696)); 3.9 cm SL (female); max. reported age: 1 years (Ref. 232)";
        $str = "61.0 cm TL (male/unsexed; (Ref. 58426)); 
        42.9 cm SL (female); 
        max. published weight: 9,500 g (Ref. 4701); 
        max. published weight: 5,000.0 g; 
        max. reported age: 13 years (Ref. 54207)";
        $str = "50.0 cm TL (male/unsexed; (Ref. 3506)); 
                133 cm TL (female); 
                max. published weight: 6,599 g (Ref. 39903); 
                max. published weight: 2,850.0 g; 
                max. reported age: 88 years (Ref. 72468)";
        
        $str = str_ireplace("unsexed;", "unsexed", $str);
        $parts = self::get_description_parts($str, false);
        print_r($parts); 
        $a = self::process_size_data($parts);
        print_r($a);
        exit;
        */
        
        self::prepare_data();
        // remove tmp dir
        $this->TEMP_FILE_PATH = str_ireplace("/fishbase", "", $this->TEMP_FILE_PATH);
        // if($this->TEMP_FILE_PATH) shell_exec("rm -fr $this->TEMP_FILE_PATH");
        recursive_rmdir($this->TEMP_FILE_PATH); // debug uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_FILE_PATH);
        if($this->test_run) return $all_taxa; //used in testing
    }
    
    private function process_size_data($parts)
    {
        $records = array();
        foreach($parts as $part)
        {
            $rec = array();
            if(stripos($part, ":") !== false) //found a colon ':'
            {   //max. reported age: 33 years (Ref. 93630)
                $arr = explode(":", $part);
                $rec["title"] = trim($arr[0]);
                $right_of_colon = trim($arr[1]);
                $arr = explode(" ", $right_of_colon);
                $rec["value"] = $arr[0];
                $rec["unit"] = $arr[1];
            }
            else
            {   //33.7 cm SL (male/unsexed (Ref. 93606))
                if($val = self::get_sex_from_size_str($part)) $rec["sex"] = $val;
                $rec["title"] = "max. size";
                $arr = explode(" ", $part);
                $rec["value"] = $arr[0];
                $rec["unit"] = $arr[1];
                if(preg_match("/" . $rec["unit"] . "(.*?)\(/ims", $part, $arr)) $rec["method"] = trim($arr[1]);
            }
            if($val = self::get_ref_id($part)) $rec["ref_id"] = $val;
            // print_r($rec);
            if($rec) $records[] = $rec;
        }
        
        print_r($records);
        
        //start creating Traitbank record
        $final = array();
        $valid_lengths = array("SL", "TL", "FL", "WD");
        foreach($records as $rec)
        {
            $r = array();
            if($rec['title'] == "max. size")
            {
                if(!in_array($rec['method'], $valid_lengths)) continue;
                if($measurement = $rec['method']) $r['measurement'] = $this->uris[$measurement];
            }
            else
            {
                $r['measurement'] = $this->uris[$rec['title']];
                $measurement = $rec['title'];
            }
            $r['value'] = $rec['value'];
            if($val = @$rec['sex'])     $r['sex']     = $this->uris[$val];
            if($val = @$rec['unit'])    $r['unit']          = $this->uris[$val];
            if($val = @$rec['ref_id'])  $r['ref_id']        = $val;
            if($val = @$this->uris["$measurement (mMethod)"])  $r['mMethod'] = $val;
            if($val = @$this->uris["$measurement (sMethod)"])  $r['sMethod'] = $val;
            if($val = @$this->uris["$measurement (mRemarks)"]) $r['mRemarks'] = $val;
            if($r) $final[] = $r;
        }
        return $final;
    }
    
    private function get_sex_from_size_str($string)
    {
        if(strpos($string, "male/unsexed") !== false) return "male/unsexed";
        elseif(strpos($string, "female") !== false) return "female";
        return false;
    }

    private function get_ref_id($string)
    {
        // if(preg_match_all("/Ref\.(.*?)\)/ims", $string, $arr)) return $arr[1];
        if(preg_match_all("/Ref\.(.*?)\)/ims", $string, $arr)) return array_map('trim', $arr[1]);
        return false;
    }
    
    private function process_habitat_data($parts)
    {
        $records = array();
        foreach($parts as $part)
        {
            $rec = array();
            if(self::is_habitat_a_range($part))
            {
                $arr = explode(" range", $part);
                $rec["title"] = $arr[0] . " range";
                
                $arr2 = explode(", usually", $arr[1]);
                $arr2 = array_map('trim', $arr2);
                
                $rec["value"] = trim(str_replace(array(":"), "", $arr2[0]));
                $rec["value"] = trim(preg_replace('/\s*\([^)]*\)/', '', $rec["value"])); //remove parenthesis
                
                if($val = self::get_range_unit($rec["value"]))
                {
                    $rec["unit"] = $val;
                    $rec["value"] = str_ireplace(" $val", "", $rec["value"]);
                }
                
                //get min max values
                $temp = explode("-", $rec["value"]);
                $temp = array_map('trim', $temp);
                $rec["min"] = @$temp[0];
                $rec["max"] = @$temp[1];
                
                
                if($val = @$arr2[1]) $rec["remarks"] = "usually " . $val;
                if($val = self::get_ref_id($part)) $rec["ref_id"] = $val;
                // print_r($rec);
            }
            else
            {
                $rec["value"] = trim(preg_replace('/\s*\([^)]*\)/', '', $part)); //remove parenthesis
                if($val = self::get_ref_id($part)) $rec["ref_id"] = $val;
                // print_r($rec);
            }
            if($rec) $records[] = $rec;
        }
        
        print_r($records);
        // exit;
        
        //start creating Traitbank record
        $final = array();
        foreach($records as $rec)
        {
            if(@$rec['title'] == "dH range") continue;
            
            if(!@$rec['title']) // meaning habitat valuese e.g. demersal, freshwater
            {
                $r = array();
                $r['measurement'] = $this->uris['habitat'];
                $measurement = $rec['value'];
                $r['value'] = $this->uris[$measurement];
                if($val = @$rec['ref_id']) $r['ref_id'] = $val;
                if($val = @$this->uris["$measurement (mRemarks)"]) $r['mRemarks'] = $val;
                if($r) $final[] = $r;
            }
            else // "pH range" OR "depth range"
            {
                $r = array();
                if($rec['title'] == "depth range")  $measurement = "mindepth";
                elseif($rec['title'] == "pH range") $measurement = "min pH";
                $r['measurement'] = $this->uris[$measurement];
                $r['value'] = $rec['min'];
                if($val = @$rec['unit'])    $r['unit']      = $this->uris[$val];
                if($val = @$rec['ref_id'])  $r['ref_id']    = $val;
                if($val = @$this->uris["$measurement (sMethod)"])  $r['sMethod'] = $val;
                if($r) $final[] = $r;
                
                $r = array();
                if($rec['title'] == "depth range")  $measurement = "maxdepth";
                elseif($rec['title'] == "pH range") $measurement = "max pH";
                $r['measurement'] = $this->uris[$measurement];
                $r['value'] = $rec['max'];
                if($val = @$rec['unit'])    $r['unit']      = $this->uris[$val];
                if($val = @$rec['ref_id'])  $r['ref_id']    = $val;
                if($val = @$this->uris["$measurement (sMethod)"])  $r['sMethod'] = $val;
                if($r) $final[] = $r;
            }
        }
        return $final;
    }
    
    private function get_range_unit($string)
    {
        $arr = explode(" ", $string);
        $char = $arr[count($arr)-1];
        if(!is_numeric(substr($char,0,1)) && !in_array($char, array("?"))) return $char;
        else return false;
    }
    
    private function is_habitat_a_range($habitat)
    {
        $ranges = array("depth range", "dH range", "pH range");
        foreach($ranges as $range)
        {
            if(stripos($habitat, $range) !== false) return true;
        }
        return false;
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
            echo("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }
    
    function prepare_data()
    {
        
        // no ref id: []
        
        // self::load_zip_contents(); //debug: uncomment in real operation
        
        
        $this->TEMP_FILE_PATH = DOC_ROOT . "tmp/fb_dir_88795";
        $this->text_path['TAXON_PATH']                       = $this->TEMP_FILE_PATH . "/taxon.txt";
        $this->text_path['TAXON_COMNAMES_PATH']              = $this->TEMP_FILE_PATH . "/taxon_comnames.txt";
        $this->text_path['TAXON_DATAOBJECT_PATH']            = $this->TEMP_FILE_PATH . "/taxon_dataobject.txt";
        $this->text_path['TAXON_DATAOBJECT_AGENT_PATH']      = $this->TEMP_FILE_PATH . "/taxon_dataobject_agent.txt";
        $this->text_path['TAXON_DATAOBJECT_REFERENCE_PATH']  = $this->TEMP_FILE_PATH . "/taxon_dataobject_reference.txt";
        $this->text_path['TAXON_REFERENCES_PATH']            = $this->TEMP_FILE_PATH . "/taxon_references.txt";
        $this->text_path['TAXON_SYNONYMS_PATH']              = $this->TEMP_FILE_PATH . "/taxon_synonyms.txt";
        
        // /*
        // self::process_taxa_references();        echo "\n taxa references -- DONE";
        // self::process_taxa();                   echo "\n taxa -- DONE";
        // self::process_taxa_comnames();          echo "\n common names -- DONE";
        // self::process_taxa_synonyms();          echo "\n synonyms -- DONE";
        // self::process_taxa_object_references(); echo "\n dataObject references -- DONE";
        // self::process_taxa_object_agents();     echo "\n agents -- DONE";
        // */
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
                if($val = @$this->taxa_ids[$taxon_id]) $taxon->acceptedNameUsageID = $val;
                else continue;
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
                   $this->agent_ids[$r->identifier] = $r->term_name;
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
            
            //get ref_id
            if(preg_match("/id=(.*?)&/ims", $ref['url'], $arr)) $ref_id = trim($arr[1]);
            elseif(preg_match("/id=(.*?)xxx/ims", $ref['url']."xxx", $arr)) $ref_id = trim($arr[1]);
            else
            {
                print_r($ref);
                echo "\nno ref id: " . $ref["url"];
                // exit("\n");
            }
            
            if(!isset($this->reference_ids[$r->identifier]))
            {
               $this->reference_ids[$ref_id] = $r->identifier;
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

        $debug = array();
        $debug["sex"] = array();
        $debug["lt"] = array();
        $debug["unit"] = array();
        $debug["habitat"] = array();
        
        foreach($taxa_objects as $taxon_id => $objects) //taxon_id is int_id in FB text file
        {
            // print_r($objects); exit;
            
            foreach($objects as $o)
            {
                foreach($o as $key => $value) $o[$key] = str_replace("\N", "", $value);

                // /* working in real operation
                if($val = @$this->taxa_ids[$taxon_id]) $taxonID = $val;
                else continue;
                // */
                
                $description = utf8_encode($o['dc_description']);

                //for TraitBank
                $rec["taxon_id"] = $taxonID;
                $rec["catnum"] = $o['dc_identifier'];
                if(substr($o['dc_source'],0,4) == "http")                          $rec["source"]      = $o['dc_source'];
                if($reference_ids = @$this->object_reference_ids[$o['int_do_id']]) $rec["referenceID"] = implode("; ", $reference_ids);
                if($agent_ids = @$this->object_agent_ids[$o['int_do_id']])         $rec["contributor"] = self::convert_agent_ids_with_names($agent_ids);
                
                if($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size")
                {
                    // echo "\n$description";
                    $str = str_ireplace("unsexed;", "unsexed", $description);
                    $parts = self::get_description_parts($str, false);
                    $items = self::process_size_data($parts);
                    foreach($items as $item)
                    {
                        /* // for stats only -- will work when looping $parts not $items
                        $debug["sex"][@$e["sex"]] = '';
                        $debug["title"][@$e["title"]] = '';
                        $debug["unit"][@$e["unit"]] = '';
                        $debug["method"][@$e["method"]] = '';
                        */
                        /*
                            [measurement] => http://rs.tdwg.org/dwc/terms/verbatimDepth
                            [value] => 0
                            [unit] => http://purl.obolibrary.org/obo/UO_0000008
                            [ref_id] => Array([0] => 58018)
                            [sMethod] => http://semanticscience.org/resource/SIO_001113
                            [sex] => http://purl.obolibrary.org/obo/PATO_0000383
                            [mMethod] => Total length; the length of a fish, measured from the tip of the snout to the tip of the longest rays of the caudal fin (but excluding filaments), when the caudal fin lobes are aligned with the main body axis.
                            [mRemarks] => demersal
                        */
                        if($val = @$item['ref_id'])
                        {
                            if($ref_ids = self::convert_FBrefID_with_archiveID($val)) $rec["referenceID"] = implode("; ", $ref_ids);
                        }
                        if($val = @$item['mMethod']) $rec['measurementMethod'] = $val;
                        if($val = @$item['sMethod']) $rec['statisticalMethod'] = $val;
                        if($val = @$item['mRemarks']) $rec['measurementRemarks'] = $val;
                        self::add_string_types($rec, $item['value'], $item['measurement'], "true");
                    }
                    /* initial stats
                    echo "\n$description";
                    $debug["sex"] = array_merge($debug["sex"], self::get_sex_from_size($description));
                    $debug["sex"] = array_unique($debug["sex"]);
                    $debug["lt"] = array_merge($debug["lt"], self::get_lengthType_from_size($description));
                    $debug["lt"] = array_unique($debug["lt"]);
                    $debug["unit"] = array_merge($debug["unit"], self::get_unit_from_size($description));
                    $debug["unit"] = array_unique($debug["unit"]);
                    */
                }
                elseif($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat")
                {
                    echo "\n$description";
                    $parts = self::get_description_parts($description, false); 
                    $a = self::process_habitat_data($parts);
                    foreach($a as $e)
                    {
                        $debug["title"]["h"][@$e["title"]] = '';
                        $debug["unit"]["h"][@$e["unit"]] = '';
                    }
                    
                }
                elseif($o['subject'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution")
                {
                    // self::add_string_types($rec, $description, "http://eol.org/schema/terms/Present", "true");
                }
                else // regular data objects
                {
                    /* debug: uncomment in normal operation
                    $mr = new \eol_schema\MediaResource();
                    $mr->taxonID        = $taxonID;
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
                    */
                }
                
                
            }
        }
        print_r($debug); exit("\n-stop-\n");

    }
    
    private function convert_FBrefID_with_archiveID($FB_ref_ids)
    {
        $final = array();
        foreach($FB_ref_ids as $id)
        {
            if($val = @$this->reference_ids[$id]) $final[] = $val;
            else echo "\nundefined ref_id:[$id]\n";
        }
        return $final;
    }
    
    private function get_uris()
    {
        /*
        $fields["sex"]          = "sex_uri";
        $fields["length_type"]  = "length_type_uri";
        $fields["habitat"]      = "habitat_uri";
        */
        $fields["value"]    = "value_uri"; //a generic spreadsheet
        $params["fields"]   = $fields;
        $params["dataset"]  = "FishBase";
        /* you can specify spreadsheet_options here, if not it will use default spreadsheet options in GBIFCountryTypeRecordAPI */
        require_library('connectors/GBIFCountryTypeRecordAPI');
        $func = new GBIFCountryTypeRecordAPI("x");
        return $func->get_uris($params, $this->uri_mappings_spreadsheet);
    }
    
    private function convert_agent_ids_with_names($agent_ids)
    {
        $arr = array();
        foreach($agent_ids as $agent_id)
        {
            if($val = @$this->agent_ids[$agent_id]) $arr[$val] = '';
        }
        $arr = array_keys($arr);
        return implode(";", $arr);
    }

    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        
        $occurrence_id = $taxon_id . '_' . $catnum;
        $occurrence_id = $catnum; // or just simply use catnum
        
        $m = new \eol_schema\MeasurementOrFact();
        $this->add_occurrence($taxon_id, $occurrence_id, $rec);
        $m->occurrenceID       = $occurrence_id;
        $m->measurementOfTaxon = $measurementOfTaxon;
        if($measurementOfTaxon == "true")
        {
            $m->source      = @$rec["source"];
            $m->contributor = @$rec["contributor"];
            if($referenceID = @$rec["referenceID"]) $m->referenceID = $referenceID;
        }
        $m->measurementType  = $measurementType;
        $m->measurementValue = $value;
        if($val = @$rec['measurementMethod']) $m->measurementMethod = $val;
        if($val = @$rec['statisticalMethod']) $m->statisticalMethod = $val;
        if($val = @$rec['measurementRemarks']) $m->measurementRemarks = $val;
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $occurrence_id, $rec)
    {
        if(isset($this->occurrence_ids[$occurrence_id])) return;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return;
    }

    private function get_sex_from_size($string)
    {
        $temp = array();
        if(preg_match_all("/\((.*?) /ims", $string, $arr)) $temp = array_merge($temp, $arr[1]);
        if(preg_match_all("/\((.*?)\)/ims", $string, $arr)) $temp = array_merge($temp, $arr[1]);
        $sex = array();
        foreach($temp as $tmp)
        {
            if(substr($tmp,0,3) == "Ref") continue;
            $arr = explode(";", $tmp);
            if($arr[0]) $sex[$arr[0]] = '';
        }
        return array_keys($sex);
    }

    private function get_lengthType_from_size($string)
    {
        if(preg_match_all("/cm (.*?)\(/ims", $string, $arr))
        {
            return array_map('trim', $arr[1]);
        }
        return array();
    }

    private function get_unit_from_size($string)
    {
        $arr = explode(" ", $string);
        return array(trim($arr[1]));
    }
    
    private function get_description_parts($string, $for_stats = true)
    {
        //bathydemersal; marine; depth range 50 - 700 m (Ref. 56504)
        if($for_stats) $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
        if($for_stats) $string = self::remove_numeric_from_string($string);
        $arr = explode(";", $string);
        return array_map('trim', $arr);
    }
    
    private function remove_numeric_from_string($string)
    {
        $digits = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0", " - ", "usually", "?");
        return str_ireplace($digits, '', $string);
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