<?php
namespace php_active_record;
/* connector: [741] IRMNG data and classification
Connector processes the DWC-A file from partner (CSV files).
Connector downloads the zip file, extracts, reads, assembles the data and generates the EOL DWC-A resource.

for blank taxonomic_status - we first check if the name is already in EOL
- if in EOL already -- we included/accepted them
- if not in EOL -- we exclude them as we don't want to create new pages for them


11169205    1383458    Navicula forcipata       Naviculaceae                                Navicula    species    Greville, 1859                       synonym     10954245
11879187    1296639    Schizonema forcipatum    Bacillariophyceae (awaiting allocation)     Schizonema  species    (Greville) Kuntze, 1898              synonym     10954245
*10954245   1265031    Fallacia forcipata       Sellaphoraceae                              Fallacia    species    (Greville) Stickle & Mann, 1990      accepted    

10918599    1383458    Navicula pseudony        Naviculaceae                                Navicula    species    Hustedt, 1955                        synonym     10691248
*10691248   1265031    Fallacia pseudony        Sellaphoraceae                              Fallacia    species    (Hustedt) D.G. Mann, 1990            accepted    

=============================
https://eol-jira.bibalex.org/browse/WEB-5489?focusedCommentId=59921&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-59921
Hi Katja, there are no more synonyms in the generated archive file.
Below is the only relationship I saw (from taxon.tab) for the names in question you mentioned (http://eol.org/pages/9879/names/synonyms)

taxonID ------ parentID ----- scientificName --------- taxonRank --- Authorship ---------- Status --- Remarks ----------------------- acceptedNameUsageID
1205718 ------ 100100 ------- Alosternida ------------ genus ------- Podaný, 1961 -------- valid -----(null) ------------------------ (null)
10655637 ----- 1205718 ------ Alosternida chalybaea -- species ----- (Haldeman, 1847) ---- valid -----(null) ------------------------ (null)
11051589 ----- 1205718 ------ Alosternida chalybaea -- species ----- Monné, 1995 --------- valid ---- Presumed chresonym (IRMNG). --- 10655637
11355212 ----- 1205718 ------ Alosternida chalybaea -- species ----- Yanega, 1996 -------- valid ---- Presumed chresonym (IRMNG). --- 10655637

Would you suggest that I just ignore/exclude taxonIDs 11051589 & 11355212, basically those with remarks "Presumed chresonym (IRMNG).".
OR maybe we just re-harvest the same resource and maybe this time our system will get the names right.
What do you think?
=============================


*/
class IrmngAPI
{
    function __construct($folder = null)
    {
        if($folder)
        {
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
            $this->occurrence_ids = array();
        }

        // $this->zip_path = "http://localhost/cp/IRMNG/IRMNG_DWC.zip";
        $this->zip_path = "https://opendata.eol.org/dataset/4e93fcec-fb78-4df9-be1b-90ac3f3a524a/resource/62e4bdfb-d6b5-4880-88ef-959195a1f638/download/irmngdwc.zip";
        $this->zip_path = "https://editors.eol.org/other_files/IRMNG/IRMNG_DWC.zip";
        // $this->zip_path = "http://www.cmar.csiro.au/datacentre/downloads/IRMNG_DWC.zip"; //no longer available from partner

        // these 2 text files were generated by a utility function
        // $this->taxa_with_blank_status_dump_file                   = "http://localhost/cp_new/IRMNG/taxa_with_blank_status.txt";
        // $this->taxa_with_blank_status_but_with_eol_page_dump_file = "http://localhost/cp_new/IRMNG/taxa_with_blank_status_but_with_eol_page.txt";
        $this->taxa_with_blank_status_dump_file                   = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/IRMNG/taxa_with_blank_status.txt";
        $this->taxa_with_blank_status_but_with_eol_page_dump_file = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/IRMNG/taxa_with_blank_status_but_with_eol_page.txt";
        
        $this->text_path = array();
        $this->names = array();
        $this->source_links["kingdom"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.list_phylum?kingdom=";
        $this->source_links["phylum"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.list_class?phylum=";
        $this->source_links["class"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.list_order?class=";
        $this->source_links["order"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.list_family?order=";
        $this->source_links["family"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.list_genera?fam_id=";
        $this->source_links["genus"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.list_species?gen_id=";
        $this->source_links["species"] = "http://www.marine.csiro.au/mirrorsearch/ir_search.go?groupchoice=any&cSub=Check+species+name%28s%29&match_type=normal&response_format=html&searchtxt=";
        $this->taxa_ids_with_blank_taxonomicStatus = array();

        $this->download_options = array('timeout' => 172800, 'download_attempts' => 2, 'delay_in_minutes' => 2);
        $this->debug = array();
        /*
        // utility 1 of 2 - saving to dump file
        // $this->TEMP_DIR = create_temp_dir() . "/";
        // $this->taxa_with_blank_status_dump_file = $this->TEMP_DIR . "taxa_with_blank_status.txt";
        
        // utility 2 of 2 - generating "taxa_with_blank_status_but_with_eol_page"
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->taxa_with_blank_status_but_with_eol_page_dump_file = $this->TEMP_DIR . "taxa_with_blank_status_but_with_eol_page.txt";
        */
        $this->list_of_taxon_ids = array();
    }

    function get_irmng_families() // utility for WEB-5220 Comparison of FALO classification to others that we have
    {
        if(!self::load_zip_contents()) return;
        print_r($this->text_path);
        $records = self::csv_to_array($this->text_path["IRMNG_DWC"], "families");
        self::remove_temp_dir();
        return $records;
    }
    
    function get_all_taxa()
    {
        if(!self::load_zip_contents()) return;
        print_r($this->text_path);
        self::csv_to_array($this->text_path["IRMNG_DWC"], "classification2"); // to get the list of taxon_ids
        self::csv_to_array($this->text_path["IRMNG_DWC"], "classification");
        /* stats
        // $a = array_keys($this->debug["TAXONOMICSTATUS"]);
        // $b = array_keys($this->debug["NOMENCLATURALSTATUS"]);
        // $c = array_keys($this->debug["TAXONRANK"]);
        // print_r($this->debug);
        */
        self::csv_to_array($this->text_path["IRMNG_DWC_SP_PROFILE"], "extant_habitat_data");
        $this->archive_builder->finalize(TRUE);
        self::remove_temp_dir();
    }
    
    private function remove_temp_dir()
    {
        // remove temp dir
        $path = $this->text_path["IRMNG_DWC"];
        $parts = pathinfo($path);
        $parts["dirname"] = str_ireplace("/IRMNG_DWC", "", $parts["dirname"]);
        recursive_rmdir($parts["dirname"]);
        debug("\n temporary directory removed: " . $parts["dirname"]);
    }
    
    private function csv_to_array($csv_file, $type)
    {
        if($type != "families")
        {
            if($val = $this->taxa_ids_with_blank_taxonomicStatus) $taxa_ids_with_blank_taxonomicStatus = $val;
            else $taxa_ids_with_blank_taxonomicStatus = self::get_taxa_ids_with_blank_taxonomicStatus();
        }
        else $taxa_ids_with_blank_taxonomicStatus = array();
        
        $i = 0;
        if(!($file = Functions::file_open($csv_file, "r"))) return;
        while(!feof($file))
        {
            $i++;
            if(($i % 50000) == 0) echo "\n [$type] $i - ";
            if($i == 1) $fields = fgetcsv($file);
            else
            {
                $rec = array();
                $temp = fgetcsv($file);
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t)
                {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                $rec = array_map('trim', $rec);
                /* stats
                $this->debug["TAXONOMICSTATUS"][$rec["TAXONOMICSTATUS"]] = '';
                $this->debug["NOMENCLATURALSTATUS"][$rec["NOMENCLATURALSTATUS"]] = '';
                $this->debug["TAXONRANK"][$rec["TAXONRANK"]] = '';
                continue;
                */
                
                if(in_array($type, array("get_taxa_ids_with_data", "extant_habitat_data"))) $taxon_id = $rec["TAXON_ID"];
                else                                                                        $taxon_id = $rec["TAXONID"];
                
                if(isset($taxa_ids_with_blank_taxonomicStatus[$taxon_id])) continue;
                
                if    ($type == "classification")           $this->create_instances_from_taxon_object($rec);
                elseif($type == "classification2")          self::get_list_of_taxon_ids($rec);
                elseif($type == "extant_habitat_data")      self::process_profile($rec);
                elseif($type == "families")
                {
                    if($rec["TAXONRANK"] == "family") $records[] = Functions::canonical_form($rec["SCIENTIFICNAME"]);
                }
            }
        }
        fclose($file);
        if($type == "get_taxa_ids_with_data") return $taxon_ids;
        if($type == "families") return array_unique($records);
    }

    private function get_list_of_taxon_ids($rec)
    {
        if($rec["TAXONOMICSTATUS"] != "valid")
        {
            if(is_numeric(stripos($rec["TAXONREMARKS"], "nomen nudum"))) return; // taxon excluded
            if(is_numeric(stripos($rec["TAXONREMARKS"], "unavailable name"))) return; // taxon excluded
        }
        if($rec["TAXONOMICSTATUS"] == "synonym")
        {
            if($rec["TAXONRANK"] != "species")  return; // won't get synonyms for level higher than species
            if(!$rec["TAXONRANK"])              return; // won't get synonyms for blank ranks
            if(!$rec["ACCEPTEDNAMEUSAGEID"])    return; // won't get synonyms for blank acceptedNameUsageID
        }
        $this->list_of_taxon_ids[$rec["TAXONID"]] = '';
    }


    private function create_instances_from_taxon_object($rec)
    {
        // if($rec["TAXONOMICSTATUS"] == "") return; --- commented this bec. it created many orphans, thus breaking the tree
        if($rec["TAXONOMICSTATUS"] != "valid")
        {
            if(is_numeric(stripos($rec["TAXONREMARKS"], "nomen nudum"))) return; // taxon excluded
            if(is_numeric(stripos($rec["TAXONREMARKS"], "unavailable name"))) return; // taxon excluded
        }
        if($rec["TAXONOMICSTATUS"] == "synonym")
        {
            return; // won't get synonyms for now, to check if IRMNG is the culprit in WEB-5489
            if($rec["TAXONRANK"] != "species")  return; // won't get synonyms for level higher than species
            if(!$rec["TAXONRANK"])              return; // won't get synonyms for blank ranks
            if(!$rec["ACCEPTEDNAMEUSAGEID"])    return; // won't get synonyms for blank acceptedNameUsageID
            if(!isset($this->list_of_taxon_ids[$rec["ACCEPTEDNAMEUSAGEID"]])) return; // won't get a synonym if acceptedNameUsageID doesn't exist
            /* stats
            if(isset($this->debug['syn'][$rec["NOMENCLATURALSTATUS"]])) $this->debug['syn'][$rec["NOMENCLATURALSTATUS"]]++;
            else                                                        $this->debug['syn'][$rec["NOMENCLATURALSTATUS"]] = 1;
            [syn] => [] => 485688
                     [orthographia] => 21539
            */
        }
        /* stats
        if(isset($this->debug['s'][$rec["TAXONOMICSTATUS"]])) $this->debug['s'][$rec["TAXONOMICSTATUS"]]++;
        else                                                  $this->debug['s'][$rec["TAXONOMICSTATUS"]] = 1;
        $temp = $rec["TAXONOMICSTATUS"]."_".$rec["NOMENCLATURALSTATUS"];
        if(isset($this->debug[$temp])) $this->debug[$temp]++;
        else                           $this->debug[$temp] = 1;
        return;
        */
        
        /* changes as of 7-Sep-2014
        - if taxon_rank is genus -- then let genus entry be blank
        - if taxon_rank is family -- then let family entry be blank
        - if taxon is a synonym but if acceptedNameUsageID doesn't exist then ignore the taxon
        */
        if($rec["TAXONRANK"] == 'genus') $rec["GENUS"] = "";
        if($rec["TAXONRANK"] == 'family') $rec["FAMILY"] = "";
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                  = $rec["TAXONID"];
        if($val = trim($rec["SCIENTIFICNAMEAUTHORSHIP"])) $taxon->scientificName = str_replace($val, "", $rec["SCIENTIFICNAME"]);
        else                                              $taxon->scientificName = $rec["SCIENTIFICNAME"];
        $taxon->family                   = $rec["FAMILY"];
        $taxon->genus                    = $rec["GENUS"];
        $taxon->taxonRank                = $rec["TAXONRANK"];
        $taxon->taxonomicStatus          = $rec["TAXONOMICSTATUS"];
        $taxon->taxonRemarks             = $rec["TAXONREMARKS"];
        $taxon->namePublishedIn          = $rec["NAMEPUBLISHEDIN"];
        $taxon->scientificNameAuthorship = $rec["SCIENTIFICNAMEAUTHORSHIP"];
        $taxon->parentNameUsageID        = $rec["PARENTNAMEUSAGEID"];
        if($rec["TAXONID"] != $rec["ACCEPTEDNAMEUSAGEID"]) $taxon->acceptedNameUsageID = $rec["ACCEPTEDNAMEUSAGEID"];

        // used so that MeasurementOrFact will have source link
        if(!in_array($taxon->taxonRank, array("family", "genus"))) $this->names[$taxon->taxonID]["n"] = $taxon->scientificName; // save only K,P,C,O & S; excludes family & genus
        $this->names[$taxon->taxonID]["r"] = $taxon->taxonRank;

        $this->archive_builder->write_object_to_file($taxon);
        
        /* allowed in EOL, but IRMNG doesn't have these 2 fields:
            http://rs.tdwg.org/ac/terms/furtherInformationURL
            http://eol.org/schema/media/referenceID
        
        not allowed in EOL, but IRMNG have these 7 fields:
            $taxon->specificEpithet          = $rec["SPECIFICEPITHET"];
            $taxon->nomenclaturalStatus      = $rec["NOMENCLATURALSTATUS"];
            $taxon->nameAccordingTo          = $rec["NAMEACCORDINGTO"];
            $taxon->parentNameUsage          = $rec["PARENTNAMEUSAGE"];
            $taxon->originalNameUsageID      = $rec["ORIGINALNAMEUSAGEID"];
            $taxon->modified                 = $rec["MODIFIED"];
            $taxon->nomenclaturalCode        = $rec["NOMENCLATURALCODE"];
        */
        /* for debug...
        $this->debug_status["rank"][$taxon->taxonRank] = 1;
        $this->debug_status[$rec["TAXONOMICSTATUS"]] = 1;
        if(isset($this->debug_status_count[$rec["TAXONOMICSTATUS"]]["count"])) $this->debug_status_count[$rec["TAXONOMICSTATUS"]]["count"]++;
        else                                                                   $this->debug_status_count[$rec["TAXONOMICSTATUS"]]["count"] = 1;
        */
        /*
        if(trim($taxon->taxonomicStatus) == "") // utility: this just saves all names with blank taxonomicStatus
        {
            self::save_to_dump(array("id" => $taxon->taxonID, "name" => $taxon->scientificName), $this->taxa_with_blank_status_dump_file);
            return false; // right now just ignore blank taxonomicStatus. once we determine which names have EOL pages then we will include those
        }
        */
    }

    private function process_profile($record)
    {
        $rec = array();
        $rec["taxon_id"] = $record["TAXON_ID"];
        if(isset($this->names[$record["TAXON_ID"]]))
        {
            $rec["rank"] = $this->names[$record["TAXON_ID"]]["r"];
            if(!in_array($rec["rank"], array("family", "genus"))) $rec["SCIENTIFICNAME"] = $this->names[$record["TAXON_ID"]]["n"];
        }
        $conservation_status = false;
        if($record["ISEXTINCT"] == "TRUE")      $conservation_status = "http://eol.org/schema/terms/extinct";
        elseif($record["ISEXTINCT"] == "FALSE") $conservation_status = "http://eol.org/schema/terms/extant";
        $habitat = false;
        if($record["ISMARINE"] == "TRUE")       $habitat = "http://purl.obolibrary.org/obo/ENVO_00000569";
        elseif($record["ISMARINE"] == "FALSE")  $habitat = "http://eol.org/schema/terms/nonMarine";
        $rec["catnum"] = "cs"; //conservation status
        if($val = $conservation_status) self::add_string_types($rec, "Conservation status", $val, "http://eol.org/schema/terms/ExtinctionStatus");
        $rec["catnum"] = "h"; //habitat
        if($val = $habitat)             self::add_string_types($rec, "Habitat", $val, "http://eol.org/schema/terms/Habitat");
    }

    private function add_string_types($rec, $label, $value, $mtype)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->measurementOfTaxon = 'true';
        // $m->measurementRemarks = ''; $m->contributor = ''; $m->measurementMethod = '';
        if(isset($rec["rank"]))
        {
            $param = "";
            if    (in_array($rec["rank"], array("kingdom", "phylum", "class", "order"))) $param = $rec["SCIENTIFICNAME"];
            elseif(in_array($rec["rank"], array("family", "genus")))                     $param = $taxon_id;
            elseif($rec["rank"] == "species")                                            $param = urlencode(trim($rec["SCIENTIFICNAME"]));
            if($param) $m->source = $this->source_links[$rec["rank"]] . $param;
        }
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        return $o;
    }

    private function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        $options = $this->download_options;
        $options['timeout'] = 999999;
        if($file_contents = Functions::get_remote_file($this->zip_path, $options))
        {
            $parts = pathinfo($this->zip_path);
            $temp_file_path = $this->TEMP_FILE_PATH . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip $temp_file_path -d $this->TEMP_FILE_PATH");
            if(!file_exists($this->TEMP_FILE_PATH . "/IRMNG_DWC_20140131.csv")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/IRMNG_DWC_20140131.csv")) return false;
            }
            $this->text_path["IRMNG_DWC"] = $this->TEMP_FILE_PATH . "/IRMNG_DWC_20140131.csv";
            $this->text_path["IRMNG_DWC_SP_PROFILE"] = $this->TEMP_FILE_PATH . "/IRMNG_DWC_SP_PROFILE_20140131.csv";
            return true;
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return false;
        }
    }

    private function save_to_dump($data, $filename) // utility
    {
        if(!($WRITE = Functions::file_open($filename, "a"))) return;
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    private function get_taxa_ids_with_blank_taxonomicStatus()
    {
        $names_with_blank_status_but_with_eol_page = self::get_names_with_blank_status_but_with_eol_page();
        $taxa_ids = array();
        // [taxa_with_blank_status_dump_file] was generated by a utility function
        $options = $this->download_options;
        $options['cache'] = 1;
        if($filename = Functions::save_remote_file_to_local($this->taxa_with_blank_status_dump_file, $options))
        {
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                if($line)
                {
                    $arr = json_decode($line, true);
                    // exclude IDs even if blank status but we already have EOL pages for them
                    if(!isset($names_with_blank_status_but_with_eol_page[$arr["name"]])) $taxa_ids[$arr["id"]] = "";
                }
            }
            echo "\n\n taxa_with_blank_status: " . count($taxa_ids);
            unlink($filename);
        }
        $this->taxa_ids_with_blank_taxonomicStatus = $taxa_ids;
        return $taxa_ids;
    }

    private function get_names_with_blank_status_but_with_eol_page()
    {
        $names = array();
        $options = $this->download_options;
        $options['cache'] = 1;
        if($filename = Functions::save_remote_file_to_local($this->taxa_with_blank_status_but_with_eol_page_dump_file, $options))
        {
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                if($val = trim($line)) $names[$val] = "";
            }
            unlink($filename);
        }
        return $names;
    }

    /*
    public function get_taxa_without_status_but_with_eol_page() // utility
    {
        $filename = Functions::save_remote_file_to_local($this->taxa_with_blank_status_dump_file, $this->download_options); // this was generated by a utility function
        $eol_api = "http://eol.org/api/search/1.0.json?exact=true&q=";
        $download_options = array('download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1); //, 'expire_seconds' => 0);
        $i = 0;
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            $i++;
            $cont = false;
            // if($i >= 1 && $i <= 100000) $cont = true;
            // if($i >= 100001 && $i <= 200000) $cont = true;
            // if($i >= 200001 && $i <= 300000) $cont = true;
            // if($i >= 300001 && $i <= 350000) $cont = true;
            // if($i >= 350001 && $i <= 360000) $cont = true;
            if($i >= 360001 && $i <= 400000) $cont = true;
            
            if(!$cont) continue;
            if($line)
            {
                echo "\n $i. ";
                $arr = json_decode($line, true);
                $arr["name"] = trim($arr["name"]);
                if(strpos($arr["name"], "×") === false) {} // not found - this is the "×" char infront of a name string e.g. "×Diaker..."; "\u00d7"
                else continue; // found - if found just ignore the name
                if($json = Functions::lookup_with_cache($eol_api . $arr["name"], $download_options))
                {
                    $taxon = json_decode($json, true);
                    echo " totalResults: " . $taxon["totalResults"];
                    if(intval($taxon["totalResults"]) > 0)
                    {
                        echo " with eol page";
                        self::save_to_dump($arr["name"], $this->taxa_with_blank_status_but_with_eol_page_dump_file);
                    }
                    else echo " without eol page";
                }
            }
        }
        echo "\n\n taxa_with_blank_status: [$i]";
        unlink($filename);
    }
    */

}
?>
