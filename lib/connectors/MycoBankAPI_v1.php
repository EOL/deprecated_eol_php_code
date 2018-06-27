<?php
namespace php_active_record;
// connector: [671] main API page: http://www.mycobank.org/Services/Generic/Help.aspx?s=searchservice
class MycoBankAPI
{
    function __construct($folder)
    {
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->synonym_ids = array();
        $this->name_id = array();
        $this->invalid_statuses = array("Orthographic variant", "Invalid", "Illegitimate", "Uncertain", "Unavailable", "Deleted");
        $this->service_search["startswith_legitimate"] = 'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=NameStatus_="Legitimate" AND Name STARTSWITH ';
        $this->service_search["startswith"] = 'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=Name STARTSWITH ';
        $this->service_search["exact"]      = 'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=Name=';
        $this->download_options = array('download_wait_time' => 5000000, 'expire_seconds' => 5184000, 'timeout' => 7200, 'delay_in_minutes' => 3); // 2 months expire_seconds
		$this->download_options['expire_seconds'] = false;
        // $this->download_options['cache_path'] = "/Volumes/Eli blue/eol_cache/"; -- no longer used
        // /*
        $this->mycobank_taxa_list              = "http://localhost/cp/MycoBank/mycobank_taxon.tab";
        $this->not_found_from_previous_harvest = "http://localhost/cp/MycoBank/not_found_from_previous_harvest.txt"; // alias names_not_yet_entered.txt
        // */
        // $this->mycobank_taxa_list              = "https://dl.dropboxusercontent.com/u/7597512/MycoBank/mycobank_taxon.tab";
        // $this->not_found_from_previous_harvest = "https://dl.dropboxusercontent.com/u/7597512/MycoBank/not_found_from_previous_harvest.txt";

        $this->dont_search_more_than_5h = array("Phoma ", "Uredo ", "Entoloma ", "Lichen ", "Patellaria ", "Hygrophorus ", "Mollisia ", "Omphalia ", "Cordyceps ", 
        "Gloeosporium ", "Collema ", "Pholiota ", "Sticta ", "Placodium ", "Biatora ", "Thelephora ", "Lycoperdon ", "Thelotrema ", "Peltigera ", "Hydnum ", "Passalora ", 
        "Pestalotia ", "Trametes ", "Pluteus ", "Peniophora ", "Candida ", "Valsa ", "Coprinus ", "Psilocybe ", "Diaporthe ", "Uromyces ", "Puccinia ", "Agaricus ",
        "Metasphaeria ", "Aspicilia ", "Poria ", "Pyrenula ", "Pleurotus ", "Acarospora ", "Catillaria ", "Alternaria ", "Sphaeropsis ", "Coniothyrium ", 
        "Helminthosporium ", "Cetraria ", "Calicium ", "Cytospora ", "Phyllosticta ", "Macrophoma ", "Hymenoscyphus ", "Aspergillus ", "Colletotrichum ", "Rhodophyllus ", 
        "Mucor ", "Peronospora ", "Porina ", "Cladosporium ", "Stereocaulon ", "Stereum ", "Rhizocarpon ", "Rhabdospora ", "Laboulbenia ", "Lentinus ", "Naucoria ", 
        "Xanthoparmelia ", "Xylaria ", "Crepidotus ", "Dasyscyphus ", "Hebeloma ", "Dicaeoma ", "Fomes ", "Arthopyrenia ", "Ramaria ", "Hygrocybe ", "Graphina ",
        "Saccharomyces ", "Physarum ", "Merulius ", "Tremella ", "Dothidea ", "Camarosporium ", "Cercospora ", "Fusarium ", "Sphaerella ", "Parmelia ", "Lecanora ",  
        "Verrucaria ", "Lecidea ", "Sphaeria ", "Ascochyta ", "Hendersonia ", "Physcia ", "Helotium ", "Boletus ", "Buellia ", "Diplodia ", "Peziza ", "Nectria ", 
        "Lepiota ", "Asterina ", "Collybia ", "Leptosphaeria ", "Pleospora ", "Erysiphe ", "Arthonia ", "Hypoxylon ", "Clitocybe ", "Graphis ", "Opegrapha ", 
        "Rinodina ", "Mycosphaerella ", "Phomopsis ", "Phyllachora ", "Pseudocercospora ", "Marasmius ", "Usnea ", "Ustilago ", "Clavaria ", "Bacidia ", "Polystictus ", 
        "Aecidium ", "Psathyrella ", "Ramularia ", "Corticium ", "Polyporus ", "Ramalina ", "Amanita ", "Tricholoma ", "Lactarius ", "Penicillium ", "Septoria ", 
        "Russula ", "Cladonia ", "Inocybe ", "Meliola ", "Caloplaca ", "Cortinarius ", "Agaricus p", "Agaricus c", "Camarosporium p", "Pertusaria ", "Sphaeronaema ", 
        "Parmelia c", "Parmelia p", "Parmelia s", "Lecanora c", "Lecanora s", "Puccinia a", "Puccinia c", "Puccinia p", "Agaricus a", "Agaricus m", "Lecidea a", 
        "Lecidea c", "Lecidea p", "Lecidea s", "Sphaeria c", "Oidium ", "Stagonospora ", "Didymosphaeria ", "Diplodina ", "Didymella ", "Mycena ", "Agaricus s", 
        "Montagnellaceae ", "Cantharellus ", "Conocybe ", "Lachnum ", "Allantoporthe ", "Eccilia ", "Phaeangium ", "Hypochnus ", "Hypocline ", "Hypocopra ", 
        "Melaspilea ", "Pseudomicrocera ", "Pseudonectria ", "Hypocrea ", "Asteridiella ", "Fungus ", "Cortinarius c", "Cortinarius p", "Cortinarius s");
        
        $this->dont_search_these_strings_as_well = array("");
        $this->dump_no = 0;
        $this->dump_no2 = 1;

        //for stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        // $this->TEMP_DIR = DOC_ROOT . '/public/tmp/mycobank/'; //debug
        $this->dump_file                        = $this->TEMP_DIR . "mycobank_dump.txt";
        $this->names_with_error_dump_file       = $this->TEMP_DIR . "names_with_error.txt"; // stores names when API timesout or has errors
        $this->more_than_1k                     = $this->TEMP_DIR . "more_than_1k.txt";
        $this->more_than_5h                     = $this->TEMP_DIR . "more_than_5h.txt";
        $this->taxa_dump_file                   = $this->TEMP_DIR . "dump_taxa.txt";
        $this->names_not_yet_entered    = array();
        $this->no_entry_parent          = array();
        $this->no_entry_current         = array();
        $this->no_entry_synonym         = array();
        $this->names_not_yet_entered_dump_file  = $this->TEMP_DIR . "names_not_yet_entered.txt"; // stores names that are missing, not yet searched, not yet cached
        $this->no_entry_parent_dump_file        = $this->TEMP_DIR . "no_entry_parent.txt";
        $this->no_entry_current_dump_file       = $this->TEMP_DIR . "no_entry_current.txt";
        $this->no_entry_synonym_dump_file       = $this->TEMP_DIR . "no_entry_synonym.txt";
        $this->debug = array();
        /*  as of Apr 28 2014
            type:
                [Basionym] => 239143
                [Combination] => 122550
                [Nomen novum] => 4203
            status:
                [Legitimate] => 365896
        */
    }

    function get_all_taxa()
    {
        // not found from last harvest
        $names = self::get_names_from_dump($this->not_found_from_previous_harvest);
        $names = array_unique($names);
        $this->save_data_to_text($names, $this->service_search["exact"], 1000);
        
        // rank higher than genus - single word taxa
        $higher_than_genus = self::get_names_list($this->mycobank_taxa_list, "higher than genus");
        $this->save_data_to_text($higher_than_genus, $this->service_search["exact"], 1000);
        
        // genus list - genus part of a multiple word taxa
        $genus_list = self::get_names_list($this->mycobank_taxa_list, "genus");
        $genus_list = self::append_space_in_string($genus_list);
        $this->save_data_to_text($genus_list, $this->service_search["startswith"], 200);
        
        //follow-up params to search
        $this->save_data_to_text(self::get_special_params($this->dont_search_more_than_5h, "5h"), $this->service_search["startswith"], 200);

        self::combine_all_dumps($this->TEMP_DIR . "partial*.*", $this->dump_file);
        
        // from original
        $this->retrieve_data_and_save_to_array();
        $this->retrieve_data_and_save_to_array2();
        $this->check_if_all_parents_have_entries();
        $this->create_instances_from_taxon_object();
        // $this->prepare_synonyms(); temporarily disabled
        $this->create_archive();
        
        // stats
        $this->no_entry_parent          = array_keys($this->no_entry_parent);
        $this->no_entry_current         = array_keys($this->no_entry_current);
        $this->no_entry_synonym         = array_keys($this->no_entry_synonym);

        $this->names_not_yet_entered    = array_merge($this->no_entry_parent, $this->no_entry_current);
        $this->names_not_yet_entered    = array_merge($this->names_not_yet_entered, $this->no_entry_synonym);
        $this->names_not_yet_entered    = array_unique($this->names_not_yet_entered);

        if($this->names_not_yet_entered) self::save_to_dump($this->names_not_yet_entered, $this->names_not_yet_entered_dump_file);
        if($this->no_entry_parent)       self::save_to_dump($this->no_entry_parent, $this->no_entry_parent_dump_file);
        if($this->no_entry_current)      self::save_to_dump($this->no_entry_current, $this->no_entry_current_dump_file);
        if($this->no_entry_synonym)      self::save_to_dump($this->no_entry_synonym, $this->no_entry_synonym_dump_file);

        echo "\n no entry parent: "         . count($this->no_entry_parent);
        echo "\n no entry current: "        . count($this->no_entry_current);
        echo "\n no entry synonym: "        . count($this->no_entry_synonym);
        echo "\n\n names_not_yet_entered (total): " . count($this->names_not_yet_entered);

        echo "\n\n some stats:";
        print_r(@$this->debug["name_type"]);
        print_r(@$this->debug["name_status"]);

        // remove temp dir
        recursive_rmdir($this->TEMP_DIR); // debug uncomment in real operation
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function get_names_from_dump($fname)
    {
        if($filename = Functions::save_remote_file_to_local($fname, $this->download_options))
        {
            if(!($READ = Functions::file_open($filename, "r"))) return;
            $contents = fread($READ, filesize($filename));
            $contents = utf8_encode($contents);
            fclose($READ);
            $params = json_decode($contents, true);
            unlink($filename);
            return $params;
        }
        return false;
    }
    
    private function save_data_to_text($params = false, $search_service = false, $searches_per_dump = 1000)
    {
        $this->dump_no++;
        $partial_dump = str_replace("mycobank_dump.txt", "partial", $this->dump_file);
        $partial_dump .= "_" . Functions::format_number_with_leading_zeros($this->dump_no, 3) . ".txt";
        
        if(!$params) $params = self::get_params_for_webservice();
        $total_params = count($params);
        $i = 0;
        foreach($params as $param)
        {
            $param = trim(ucfirst($param));
            print "\n searching:[$param]";
            $i++;
            if(($i % $searches_per_dump) == 0)
            {
                $this->dump_no++;
                $partial_dump = str_replace("mycobank_dump.txt", "partial", $this->dump_file);
                $partial_dump .= "_" . Functions::format_number_with_leading_zeros($this->dump_no, 3) . ".txt"; 
            }
            
            /*
            $cont = false;
            // if($i >= 1 && $i < 7000) $cont = true;
            // if($i >= 7000 && $i < 14000) $cont = true;
            // if($i >= 14000 && $i < 21000) $cont = true;
            if(!$cont) continue;
            */
            
            if(in_array($param, $this->dont_search_more_than_5h))
            {
                print "\n [$param] must not be searched... \n";
                continue;
            }
            elseif(in_array($param, $this->dont_search_these_strings_as_well))
            {
                print "\n [$param] must not be searched... \n";
                continue;
            }
            
            $no_of_results = 0;
            if($val = $search_service) $url = $val . '"' . $param . '"';
            else                       $url = $this->service_search["startswith"] . '"' . $param . '"';
            echo "\n[$param] $i of $total_params \n";
            if($contents = Functions::lookup_with_cache($url, $this->download_options))
            {
                if($response = simplexml_load_string($contents))
                {
                    if(isset($response->ErrorMessage))
                    {
                        echo "\n investigate error [$param]: " . $response->ErrorMessage . "\n";
                        sleep(120); // 2mins
                        echo "\n access failed [$param] ... \n";
                        self::save_to_dump($param, $this->names_with_error_dump_file);
                        continue;
                    }

                    $no_of_results = count($response);
                    if($no_of_results > 0)
                    {
                        echo " - count: $no_of_results";
                        if($no_of_results >= 500 && $no_of_results < 900) self::save_to_dump($param . "\t" . $no_of_results, $this->more_than_5h);
                        if($no_of_results >= 900)                         self::save_to_dump($param . "\t" . $no_of_results, $this->more_than_1k);

                        $records = array();
                        foreach($response as $rec)
                        {
                            $hierarchy = "";
                            $source_url = "";
                            $parent = "";
                            if(preg_match("/title\='(.*?)'/ims", $rec->Classification_, $arr))
                            {
                                $hierarchy = $arr[1];
                                $parent = self::get_parent_from_hierarchy($hierarchy);
                            }
                            
                            $rec_id = "";
                            if(preg_match("/;Rec\=(.*?)\&/ims", $rec->Classification_, $arr)) $rec_id = $arr[1];
                            if(preg_match("/href\='(.*?)'/ims", $rec->Classification_, $arr)) $source_url = str_ireplace("&amp;", "&", $arr[1]);
                            $records[] = array("n"  => (string) $rec->Name,
                                               "cn" => (string) $rec->CurrentName_Pt_,
                                               "r"  => (string) $rec->Rank_Pt_,
                                               "nt" => (string) $rec->NameType_,
                                               "ns" => (string) $rec->NameStatus_,
                                               "a"  => (string) $rec->Authors_,
                                               "p"  => $parent,
                                               "h"  => $hierarchy,
                                               "s"  => $source_url,
                                               "t"  => (string) $rec->MycoBankNr_,
                                               "d"  => (string) $rec_id,
                                               "y"  => (string) $rec->NameYear_,
                                               "e3" => (string) $rec->E3787,
                                               "e4" => (string) $rec->E4060,
                                               "so" => (string) $rec->ObligateSynonyms_Pt_,
                                               "sf" => (string) $rec->FacultativeSynonyms_Pt_);
                        }
                        $temp = array();
                        $temp[$param] = $records;
                        self::save_to_dump($temp, $partial_dump);
                    }
                    else
                    {
                        echo "\n no result for: [$param]\n";
                        /* decided not to save params with zero records anymore - 14Jul2014
                        // save even with no records, so it won't be searched again...
                        $temp = array();
                        $temp[$param] = array();
                        self::save_to_dump($temp, $partial_dump);
                        */
                    }
                }
            }
            else
            {
                echo "\n access failed [$param] ... \n";
                self::save_to_dump($param, $this->names_with_error_dump_file);
            }
            self::sleep_now($no_of_results);
        }
    }
    
    function combine_all_dumps($files, $dump_file)
    {
        echo "\n\n Start compiling all dumps...";
        if(!($OUT = fopen($dump_file, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $dump_file);
          return;
        }
        foreach (glob($files) as $filename)
        {
            echo "\n -- $filename";
            if(!($READ = fopen($filename, "r")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
              return;
            }
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents) fwrite($OUT, $contents);
            else print "\n no contents [$filename]";
        }
        fclose($OUT);
        print "\n All dumps compiled\n\n";
    }

    private function get_pt_value($string, $return = false)
    {
        $records = array();
        if(preg_match_all("/<TargetRecord>(.*?)<\/TargetRecord>/ims", $string, $arr))
        {
            foreach($arr[1] as $line)
            {
                $id = ""; $name = "";
                if(preg_match("/<Name>(.*?)<\/Name>/ims", $line, $arr2)) $name = trim($arr2[1]);
                if(preg_match("/<Id>(.*?)<\/Id>/ims", $line, $arr2)) $id = trim($arr2[1]);
                if($name && $id) $records[] = array("Id" => $id, "Name" => $name);
            }
        }
        if($return) return @$records[0][$return];
        else return $records;
    }
    
    private function retrieve_data_and_save_to_array($dump_file = false)
    {
        if(!$dump_file) $dump_file = $this->dump_file;
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $records = json_decode($line, true);
                if(!$records) continue;
                foreach($records as $key => $recs) // $key is the string param used in the search
                {
                    foreach($recs as $rec)
                    {
                        $rec = array_map('trim', $rec);
                        $name = (string) $rec["n"];
                        $status = $rec["ns"];
                        if(!$name) continue;
                        if(!isset($this->name_id[$name][$status])) self::assign_record_to_array($rec);
                        else
                        {
                            if($rec["y"] > $this->name_id[$name][$status]["y"] || in_array($this->name_id[$name][$status]["p"], array("-", "?", ""))) self::assign_record_to_array($rec);
                        }
                    }
                }
            }
        }
    }

    private function assign_record_to_array($rec)
    {
        $name = (string) $rec["n"];
        $status = $rec["ns"];
        $this->name_id[$name][$status]["a"] = $rec["a"];
        $this->name_id[$name][$status]["p"] = $rec["p"];
        $this->name_id[$name][$status]["s"] = $rec["s"];
        $this->name_id[$name][$status]["t"] = $rec["t"];
        $this->name_id[$name][$status]["y"] = $rec["y"];
        $this->name_id[$name][$status]["d"] = $rec["d"];
        // good source for taxon remarks
        // $this->name_id[$name][$status]["e3"] = $rec["e3"];
        // $this->name_id[$name][$status]["e4"] = $rec["e4"];
        $this->name_id[$name][$status]["cn"] = $rec["cn"];
        $this->name_id[$name][$status]["r"]  = $rec["r"];
        $this->name_id[$name][$status]["nt"] = $rec["nt"];
        $this->name_id[$name][$status]["ns"] = $rec["ns"];
        $this->name_id[$name][$status]["so"] = $rec["so"];
        $this->name_id[$name][$status]["sf"] = $rec["sf"];
        $this->name_id[$name][$status]["h"] = $rec["h"]; // good to cache hierarchy info, access it only when needed
    }

    private function retrieve_data_and_save_to_array2($dump_file = false)
    {
        $this->id_rec = array();
        if(!$dump_file) $dump_file = $this->dump_file;
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $records = json_decode($line, true);
                if(!$records) continue;
                foreach($records as $key => $recs) // $key is the string param used in the search
                {
                    foreach($recs as $rec)
                    {
                        $rec = array_map('trim', $rec);
                        $rec_id = $rec["d"];
                        $t      = $rec["t"]; //MycoBank id
                        $n      = $rec["n"];
                        if(!$rec_id || !$t)
                        {
                            echo "\n investigate: invalid rec_id: [$rec_id][$t][$n]";
                            continue;
                        }
                        if(!isset($this->id_rec[$rec_id])) self::assign_record_to_array2($rec);
                    }
                }
            }
        }
        echo "\n count:" . count($this->id_rec) . "\n";
    }

    private function assign_record_to_array2($rec)
    {
        $rec_id = $rec["d"];
        $this->id_rec[$rec_id]["a"] = $rec["a"];
        $this->id_rec[$rec_id]["t"] = $rec["t"];
        if($rec["ns"] == "Legitimate") $ns = "L";
        else $ns = "";
        $this->id_rec[$rec_id]["ns"] = $ns;
    }
    
    private function check_if_all_parents_have_entries() // a utility
    {
        echo "\n start of report: taxa that are 'invalid/illegitimate/wrong'";
        foreach($this->name_id as $taxon => $record)
        {
            $value = array();
            if($val = @$record["Legitimate"]) $value = $val;
            if(!$value) continue;
            $parent = (string) trim($value["p"]);
            if(!in_array($parent, array("-", "?", "")))
            {
                if(!isset($this->name_id[$parent]))
                {
                    echo "\n investigate: $taxon [$parent] no entry for this parent taxon";
                    $parent = trim($parent);
                    $this->no_entry_parent[$parent] = '';
                }
            }
            if($parent == "?") echo "\n investigate parent is ? for [$taxon][" . $value["t"] . "]";
        }
        echo "\n - end of report -\n\n";
    }
    
    function create_instances_from_taxon_object()
    {
        $k = 0;
        $total = count($this->name_id);
        foreach($this->name_id as $sciname => $record)
        {
            $k++;
            // echo "\n create taxon $k of $total";
            $rec = array();
            if($val = @$record["Legitimate"]) $rec = $val;
            if(!$rec) continue;
            $parent = "";
            $taxon_id = "";
            $parentNameUsageID = "";
            if(!$taxon_id = @$rec["t"])
            {
                echo "\n investigate: no taxon_id for [$sciname]";
                continue;
            }
            if(in_array($sciname, array("-", "?"))) continue;
            $rank = self::get_rank($rec["r"], $sciname);
            if(in_array(@$rec["ns"], $this->invalid_statuses) && in_array($rank, array("species", "subspecies"))) continue; //exclude invalid taxa for 'species' and 'subspecies'
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = $taxon_id;
            $taxon->scientificName              = $sciname;
            $taxon->scientificNameAuthorship    = @$rec["a"];
            $taxon->taxonRank                   = $rank;
            $taxon->acceptedNameUsageID         = "";
            // $taxon->furtherInformationURL       = @$rec["s"];
            /*
            $taxon->taxonomicStatus             = self::get_taxonomic_status(@$rec["ns"]);
            $taxon->taxonRemarks                = self::get_taxon_remark($rec);
            */
            //stats 
            /*
            if(isset($this->debug["name_status"][@$rec["ns"]])) $this->debug["name_status"][@$rec["ns"]]++;
            else                                                $this->debug["name_status"][@$rec["ns"]] = 1;
            if(isset($this->debug["name_type"][@$rec["nt"]]))   $this->debug["name_type"][@$rec["nt"]]++;
            else                                                $this->debug["name_type"][@$rec["nt"]] = 1;
            */
            
            $parent_info = self::get_parent_info($rec);
            $parentNameUsageID  = $parent_info["parentNameUsageID"];
            $parent_status      = $parent_info["parent_status"]; // to be used below
            
            if(!in_array(trim($parentNameUsageID), array("-", "?", ""))) $taxon->parentNameUsageID = $parentNameUsageID;

            $current_name_rec_id    = self::get_pt_value($rec["cn"], "Id");
            $current_name           = self::get_pt_value($rec["cn"], "Name");
            if($current_name_id = @$this->id_rec[$current_name_rec_id]["t"])
            {
                if($current_name_id != $taxon_id)
                {
                    $taxon->acceptedNameUsageID = $current_name_id;
                    $taxon->taxonomicStatus = "synonym";
                }
            }
            else
            {
                if($sciname != $current_name && $current_name != "")
                {
                    echo "\n investigate missing current_name [$current_name_rec_id][$current_name]"; //should not go here
                    echo "\n orig: [$taxon->taxonID][$taxon->scientificName]\n";
                    $taxon->acceptedNameUsageID = "";
                    $this->no_entry_current[$current_name] = '';
                }
            }

            $this->name_id[$sciname]["Legitimate"]["syn"] = false;
            if($sciname != $current_name && $current_name != "")
            {
                /* if sciname != current_name and current_name not blank - then if sciname's parent is Illegitimate - ignore sciname */
                if(in_array($parent_status, $this->invalid_statuses)) continue;
                // e.g. Selenia perforans will be ignored since the genus Selenia is Illegitimate and current_name anyway exists Montagnula perforans
                $this->name_id[$sciname]["Legitimate"]["syn"] = true;
            }
            
            if(!isset($this->taxa[$taxon->taxonID]))
            {
                $this->taxa[$taxon->taxonID] = '';
                if($taxon->acceptedNameUsageID == $taxon->taxonID)
                {
                    $taxon->acceptedNameUsageID = "";
                    $taxon->taxonomicStatus = "";
                    echo "\n should not go here \n";
                }
                $this->archive_builder->write_object_to_file($taxon);
                // if(($k % 5) == 0) $this->dump_no2++; //orig 50000
                // $filename = str_replace("mycobank_dump.txt", "taxa", $this->dump_file);
                // $filename .= "_" . Functions::format_number_with_leading_zeros($this->dump_no2, 2) . ".txt"; 
                // self::save_to_dump($taxon, $filename);
            }
        }
    }
    
    private function get_taxonomic_status($status) // only Legitimate should pass through here
    {
        if($status == "Legitimate") return "valid";
        else echo "\n investigate: status undefined [$status]\n";
    }
    
    private function get_parent_info($rec)
    {
        $hierarchy = $rec["h"];
        $hierarchy = explode(",", $hierarchy);
        $count = count($hierarchy);
        $parents = array();
        for($i = $count-1; $i >= 0; $i--)
        {
            if(!in_array(trim($hierarchy[$i]), array("?", "-", "")))
            {
                $parent = trim($hierarchy[$i]);
                $parents[] = $parent; // to be used below
                if($val = self::get_parent_info_detail($parent)) return $val;
            }
        }
        
        // try all other invalid statuses
        if($parents)
        {
            foreach($parents as $parent)
            {
                foreach($this->invalid_statuses as $invalid_status)
                {
                    if($val = @$this->name_id[$parent][$invalid_status]["t"])
                    {
                        $parentNameUsageID = $val;
                        $parent_status = @$this->name_id[$parent][$invalid_status]["ns"];
                        return array("parentNameUsageID" => $parentNameUsageID, "parent_status" => $parent_status);
                    }
                }
            }
            $this->no_entry_parent[$parents[0]] = ''; // for checking - stats
        }
        
    }
    
    private function get_parent_info_detail($parent)
    {
        if(!in_array(trim($parent), array("-", "?", ""))) // this filter maybe done already above
        {
            $parentNameUsageID  = "";
            $parent_status      = "";
            
            if    ($val = @$this->name_id[$parent]["Legitimate"]["t"])
            {
                $parentNameUsageID = $val;
                $parent_status = @$this->name_id[$parent]["Legitimate"]["ns"];
                return array("parentNameUsageID" => $parentNameUsageID, "parent_status" => $parent_status);
            }
            else // if parent is not legit, then check if current_name of parent is good
            {
                /* 
                e.g. species "Chamaeceras brasiliensis" (449153) has a parent = "Chamaeceras" which is invalid, so we got the current_name of "Chamaeceras" which is "Marasmius" (56879)
                e.g. species "Sphaerella tini" (319124) has a parent = "Sphaerella" but is invalid and there is no current_name. So we move to the next parent which is "Mycosphaerellaceae" (92960)
                */
                foreach($this->invalid_statuses as $invalid_status)
                {
                    if($current_name = @$this->name_id[$parent][$invalid_status]["cn"])
                    {
                        $current_name = self::get_pt_value($current_name, "Name");
                        if($val = @$this->name_id[$current_name]["Legitimate"]["t"])
                        {
                            $parentNameUsageID = $val;
                            $parent_status = @$this->name_id[$current_name]["Legitimate"]["ns"];
                            return array("parentNameUsageID" => $parentNameUsageID, "parent_status" => $parent_status);
                        }
                    }
                }
            }
            
        }
        return false;
    }
    
    private function prepare_synonyms()
    {
        $i = 0;
        $total = count($this->name_id);
        foreach($this->name_id as $sciname => $record)
        {
            $i++;
            // echo "\n syn $i of $total";
            if($rec = @$record["Legitimate"])
            {
                if(!in_array(@$rec["ns"], $this->invalid_statuses)) // create synonyms only for Legitimate taxa
                {
                    if(!$rec["syn"]) // we will not be getting synonyms of known synonyms. e.g. won't get synonyms of Selenia perforans, since it is already a synonym of Montagnula perforans
                    {
                        if($taxon_id = @$rec["t"])
                        {
                            if($rec["sf"]) self::create_synonyms($rec["sf"], $taxon_id, "Facultative synonym of $sciname");
                            if($rec["so"]) self::create_synonyms($rec["so"], $taxon_id, "Obligate synonym of $sciname");
                        }
                    }
                }
            }
            else continue;
        }
    }

    private function create_synonyms($records, $taxon_id, $taxon_remarks)
    {
        $records = self::get_pt_value($records);
        foreach($records as $rec)
        {
            if($syn_id =  @$this->id_rec[$rec["Id"]]["t"])
            {
                if(@$this->id_rec[$rec["Id"]]["ns"] != "L") continue;
                $synonym = new \eol_schema\Taxon();
                $synonym->taxonID                       = $syn_id;
                $synonym->scientificName                = $rec["Name"];
                $synonym->scientificNameAuthorship      = @$this->id_rec[$rec["Id"]]["a"];
                // $synonym->taxonRank                     = self::get_rank(@$this->id_rec[$rec["Id"]]["r"]);
                $synonym->acceptedNameUsageID           = $taxon_id;
                $synonym->taxonomicStatus               = "synonym";
                $synonym->taxonRemarks                  = $taxon_remarks;
                if(!$synonym->scientificName) continue;
                /* working well, this happens if create_instances_from_taxon_object() goes first before prepare_synonyms() and $this->taxa[ID] = $taxon class
                if(isset($this->taxa[$synonym->taxonID])) // this means that a Legitimate taxon has already been entered
                {
                    echo "\n investigate:[$synonym->taxonID] a Legitimate taxon has already been entered\n";
                    $taxon = $this->taxa[$synonym->taxonID];
                    if($taxon->acceptedNameUsageID == "")
                    {
                        // no need to investigate, synonym without current_name from service: ";
                        // echo "[$taxon->taxonID] - [$taxon->scientificName][$taxon->acceptedNameUsageID][$taxon_id][$taxon_remarks]" . $rec["Id"] . "\n";
                        $taxon->acceptedNameUsageID = $taxon_id;
                        $taxon->taxonomicStatus = "synonym";
                        $this->taxa[$synonym->taxonID] = $taxon;
                    }
                    // else...here we leave it alone
                }
                else
                {
                    if(!isset($this->synonym_ids[$synonym->taxonID]))
                    {
                        $this->archive_builder->write_object_to_file($synonym);
                        $this->synonym_ids[$synonym->taxonID] = '';
                    }
                }
                */
                if(!isset($this->synonym_ids[$synonym->taxonID]))
                {
                    $this->archive_builder->write_object_to_file($synonym);
                    $this->synonym_ids[$synonym->taxonID] = '';
                }
            }
            else
            {   //  decided not to add a synonym if it has no entry
                $this->no_entry_synonym[$rec["Name"]] = '';
                echo "\n investigate synonym [" . $rec["Name"] . "] is not in this->id_rec yet \n";
                continue;
            }
        }
    }
    
    private function get_taxon_remark($rec)
    {
        $rem = "";
        if($val = $rec["y"])  $rem .= "Name year: " . $val . "<br>";
        if($val = $rec["nt"]) $rem .= "Name type: " . $val . "<br>";
        if($val = $rec["ns"]) $rem .= "Name status: " . $val . "<br>";
        if($val = $rec["e3"]) $rem .= "<br>" . $val . "<br>";
        if($val = $rec["e4"]) $rem .= "<br>" . $val . "<br>";
        return $rem;
    }
    
    private function get_rank($rank, $sciname = null)
    {
        $rank = self::get_pt_value($rank, "Name");
        switch ($rank) 
        {
            case "gen.":    return "genus";
            case "sp.":     return "species";
            case "var.":    return "varietas";
            case "subsp.":  return "subspecies";
            case "sect.":   return "section";
            case "f.":      return "forma";
            case "ser.":    return "series";
            case "subser.": return "subseries";
            case "subg.":   return "subgenus";
            case "subgen.": return "subgenus";
            case "subsp.":  return "subspecies";
            case "subvar.": return "subvariety";
            case "ssp.":    return "subspecies";
            case "subf.":   return "subform";
            case "ordo":    return "order";
            case "subordo": return "suborder";
            case "fam.":    return "family";
            case "subdiv.": return "subdivision";
            case "div.":    return "division";
            case "cl.":     return "class";
            case "subcl.":  return "subclass";
            case "tr.":     return "tribe";
            case "subsect.": return "subsection";
            case "subfam.":  return "subfamily";
            case "subtr.":   return "subtribe";
            // below this line are unrecognized ranks
            case "trF.":     return "trF.";
            case "subdivF.": return "subdivF.";
            case "race":     return "race";
            case "subregn.": return "subregn.";
            case "*":        return "";
            case "stirps":   return "stirps";
            case "f.sp.":    return "f.sp.";
            case "regn.":    return "regn.";
            default:
                if($rank) echo "\n investigate: rank for [$sciname] not yet initialized [$rank]\n";
                return "";
        }
    }
    
    private function get_params_for_webservice()
    {
        $params = array();
        $letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
        $letters1 = explode(",", $letters);
        $letters2 = explode(",", $letters);
        $letters3 = explode(",", $letters);
        foreach($letters1 as $L1)
        {
            foreach($letters2 as $L2)
            {
                foreach($letters3 as $L3)
                {
                    $params[] = ucfirst(strtolower("$L1$L2$L3"));
                }
            }
        }
        return $params;
    }
    
    private function retrieve_names_searched($dump_file = false) // a utility, may not be needed anymore
    {
        $names = array();
        if(!$dump_file) $dump_file = $this->dump_file;
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $records = json_decode($line, true);
                if(!$records) continue;
                foreach($records as $key => $recs) $names[$key] = ""; // $key is the string param used in the search
            }
        }
        return array_keys($names);
    }
    
    private function get_parent_from_hierarchy($hierarchy)
    {
        $hierarchy = explode(",", $hierarchy);
        $count = count($hierarchy);
        for($i = $count-1; $i >= 0; $i--)
        {
            if(!in_array(trim($hierarchy[$i]), array("?", "-", ""))) return trim($hierarchy[$i]);
        }
        return trim(@$hierarchy[$i]);
    }

    private function get_names_no_entry_from_partner($type) // utility
    {
        $names = array();
        $dump_file = DOC_ROOT . "/public/tmp/mycobank_latest/" . $type . ".txt";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }

    private function get_names_from_dump_local($basename, $type)
    {
        $names = array();
        $dump_file = DOC_ROOT . "/public/tmp/mycobank_latest/" . $basename . ".txt";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if(!$line) continue;
            $parts = explode(" ", $line);
            if($type == "genus")
            {
                if(@$parts[0] && count($parts) > 1) $names[$parts[0]] = '';
            }
            else
            {
                if(count($parts) == 1) $names[$parts[0]] = '';
            }
        }
        return array_keys($names);
    }

    private function sleep_now($results)
    {
        usleep(500000);
        // if($results > 1000) sleep(20);
        // elseif($results > 500) sleep(15);
        // elseif($results > 250) sleep(10);
        // elseif($results > 100) sleep(5);
        // elseif($results > 50) sleep(5);
        // else usleep(500000);
    }

    private function create_archive_xxx()
    {
        self::combine_all_dumps($this->TEMP_DIR . "taxa*.*", $this->taxa_dump_file);
        foreach(new FileIterator($this->taxa_dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $rec = json_decode($line, true);
                print_r($rec);
            }
        }
    }

    private function create_archive()
    {
        // $total = count($this->taxa);
        // $i = 0;
        // foreach($this->taxa as $t)
        // {
        //     $i++;
        //     echo "\n saving $i of $total";
        //     if($t->acceptedNameUsageID == $t->taxonID) $t->acceptedNameUsageID = "";
        //     $this->archive_builder->write_object_to_file($t);
        // }
        $this->archive_builder->finalize(TRUE);
    }

    private function save_to_dump($data, $filename)
    {
        if(!($WRITE = fopen($filename, "a")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        if($data && is_array($data)) fwrite($WRITE, json_encode($data, true) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    private function initialize_dump_file()
    {
        if(!($WRITE = fopen($this->dump_file, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->dump_file);
          return;
        }
        fclose($WRITE);
    }

    private function append_space_in_string($names)
    {
        $list = array();
        foreach($names as $name) $list[] = $name . " ";
        return $list;
    }

    private function get_names_list($fname, $type) // type = "genus" or "higher than genus" - but this is just based on name string, not actual rank
    {
        $counts = array();
        $names = array();
        $options = $this->download_options;
        $options['cache'] = 1; // debug orig should be 1
        if($filename = Functions::save_remote_file_to_local($fname, $options))
        {
            foreach(new FileIterator($filename) as $line_number => $line)
            {
                if($line)
                {
                    $line = trim($line);
                    $values = explode("\t", $line);
                    $parts = explode(" ", $values[3]); // scientificName is 4th column thus index key = 3
                    if($type == "genus")
                    {
                        if(@$parts[0] && count($parts) > 1) $names[$parts[0]] = '';
                    }
                    else
                    {
                        if(count($parts) == 1) $names[$parts[0]] = '';
                    }
                }
            }
            unlink($filename);
        }
        $names = array_keys($names);
        array_shift($names);
        return $names;
    }

    private function get_special_params($arr, $type)
    {
        $params = array();
        $letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
        $letters1 = $arr;
        $letters2 = explode(",", $letters);
        $letters3 = explode(",", $letters);
        foreach($letters1 as $L1)
        {
            foreach($letters2 as $L2)
            {
                if($type == "1k") // 3 loops
                {
                    foreach($letters3 as $L3)
                    {
                        $term = ucfirst(strtolower("$L1$L2$L3"));
                        $params[] = $term;
                    }
                }
                else // 2 loops
                {
                    $term = ucfirst(strtolower("$L1$L2"));
                    $params[] = $term;
                }
            }
        }
        return $params;
    }

}
?>