<?php
namespace php_active_record;
// connector: [671]
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
        // $this->service =            'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=NameStatus_="Legitimate" AND Name STARTSWITH ';
        $this->service =            'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=Name STARTSWITH ';
        $this->service_exact_name = 'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=Name=';
        //for stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->dump_file = $this->TEMP_DIR . "mycobank_dump.txt";
        $this->names_not_yet_entered_dump_file  = $this->TEMP_DIR . "names_not_yet_entered.txt";
        $this->names_with_error_dump_file       = $this->TEMP_DIR . "names_with_error.txt";
        $this->no_entry_parent          = array();
        $this->no_entry_current_name    = array();
        $this->names_not_yet_entered    = array();
        $this->debug                    = array();
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
        /* to be used if you ALREADY HAVE generated dump files.
        $file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
        $this->retrieve_data_and_save_to_array($file);
        $this->retrieve_data_and_save_to_array2($file);
        */
        
        // /* to be used if you HAVE NOT yet generated dump files
        $this->save_data_to_text();
        $this->retrieve_data_and_save_to_array();
        $this->retrieve_data_and_save_to_array2();
        // */

        $this->check_if_all_parents_have_entries();
        $this->create_instances_from_taxon_object();
        $this->prepare_synonyms(); 
        $this->create_archive();
        
        // stats
        $this->names_not_yet_entered = array_keys($this->names_not_yet_entered);
        echo "\n names_not_yet_entered: " . count($this->names_not_yet_entered);
        echo "\n saved in: [$this->names_not_yet_entered_dump_file]\n";
        if($this->names_not_yet_entered) self::save_to_dump($this->names_not_yet_entered, $this->names_not_yet_entered_dump_file);
        echo "\n\n no entry parent: " . count($this->no_entry_parent);
        echo "\n no entry current: " . count($this->no_entry_current_name) . "\n";
        $temp = array_merge(array_keys($this->no_entry_parent), array_keys($this->no_entry_current_name));
        $temp = array_unique($temp);
        echo "\n total no entry: " . count($temp) . "\n";
        echo "\n\n some stats:";
        print_r($this->debug["name_type"]);
        print_r($this->debug["name_status"]);

        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function save_data_to_text($params = false)
    {
        /* stats $names_with_error = self::get_names_no_entry_from_partner("names_with_error"); */
        $names_with_error = array();
        if(!$params) $params = self::get_params_for_webservice();
        $total_params = count($params);
        self::initialize_dump_file();
        $i = 0;
        foreach($params as $param)
        {
            $param = ucfirst($param);
            print "\n $param";
            $i++;
            
            /* 
            $cont = false;
            // if($i >= 1 && $i < 100) $cont = true;
            // if($i >= 100 && $i < 1000) $cont = true;
            // if($i >= 200 && $i < 15000) $cont = true;
            // if($i >= 15000 && $i < 20000) $cont = true;
            // if($i >= 20000 && $i < 90000) $cont = true;
            if(!$cont) continue;
            */
            
            // if(in_array($param, $names_with_error)) continue;

            $no_of_results = 0;            
            $url = $this->service . '"' . $param . '"';
            // $url = $this->service_exact_name . '"' . $param . '"';
            echo "\n[$param] $i of $total_params \n";
            if($contents = Functions::lookup_with_cache($url, array('timeout' => 7200, 'download_attempts' => 1)))
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
                            if(preg_match("/href\='(.*?)'/ims", $rec->Classification_, $arr)) $source_url = $arr[1];
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
                        self::save_to_dump($temp, $this->dump_file);
                    }
                    else
                    {
                        echo "\n no result for: [$param]\n";
                        // save even with no records, so it won't be searched again...
                        $temp = array();
                        $temp[$param] = array();
                        self::save_to_dump($temp, $this->dump_file);
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
        // $this->name_id[$name][$status]["h"] = $rec["h"]; // good to cache hierarchy info, access it only when needed
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
        $invalid_species = array();
        $invalid_higher_taxa = array();
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
                    $this->no_entry_parent[$parent] = 1;
                    $parts = explode(" ", $parent);
                    if(count($parts) > 1)   $invalid_species[$parent] = 1;
                    else                    $invalid_higher_taxa[$parent] = 1;
                    $this->names_not_yet_entered[$parent] = 1;
                }
            }
            if($parent == "?") echo "\n investigate parent is ? for [$taxon][" . $value["t"] . "]";
        }
        echo "\n count of invalid species: " . count($invalid_species);
        echo "\n count of invalid higher taxa: " . count($invalid_higher_taxa);
        echo "\n - end of report -\n\n";
    }
    
    function create_instances_from_taxon_object()
    {
        foreach($this->name_id as $sciname => $record)
        {
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
            /*
            $taxon->taxonomicStatus             = self::get_taxonomic_status(@$rec["ns"]);
            $taxon->furtherInformationURL       = @$rec["s"];
            $taxon->taxonRemarks                = self::get_taxon_remark($rec);
            */
            //stats
            if(isset($this->debug["name_status"][@$rec["ns"]])) $this->debug["name_status"][@$rec["ns"]]++;
            else                                                $this->debug["name_status"][@$rec["ns"]] = 1;
            if(isset($this->debug["name_type"][@$rec["nt"]]))   $this->debug["name_type"][@$rec["nt"]]++;
            else                                                $this->debug["name_type"][@$rec["nt"]] = 1;

            $parent_info = self::get_parent_info($rec);
            $parentNameUsageID  = $parent_info["parentNameUsageID"];
            $parent_status      = $parent_info["parent_status"]; // to be used below
            if(!in_array(trim($parentNameUsageID), array("-", "?", ""))) $taxon->parentNameUsageID = $parentNameUsageID;

            $current_name_rec_id    = self::get_pt_value($rec["cn"], "Id");
            $current_name           = self::get_pt_value($rec["cn"], "Name");
            if($current_name_id = @$this->id_rec[$current_name_rec_id]["t"]) $taxon->acceptedNameUsageID = $current_name_id;
            else
            {
                if($sciname != $current_name && $current_name != "")
                {
                    echo "\n investigate will fill up missing current_name [$current_name_rec_id][$current_name]"; //should not go here
                    echo "\n orig: [$taxon->taxonID][$taxon->scientificName]\n";
                    $taxon->acceptedNameUsageID = str_replace(" ", "_", $current_name);
                    $this->no_entry_current_name[$current_name] = 1;
                    $this->names_not_yet_entered[$current_name] = 1;
                }
            }

            $this->name_id[$sciname]["Legitimate"]["syn"] = 0;
            if($sciname != $current_name && $current_name != "")
            {
                /* if sciname != current_name and current_name not blank - then if sciname's parent is Illegitimate - ignore sciname */
                if(in_array($parent_status, $this->invalid_statuses)) continue;
                // e.g. Selenia perforans will be ignored since the genus Selenia is Illegitimate and current_name anyway exists Montagnula perforans
                $this->name_id[$sciname]["Legitimate"]["syn"] = 1;
            }
            
            if(!isset($this->taxa[$taxon->taxonID])) $this->taxa[$taxon->taxonID] = $taxon;
        }
    }
    
    private function get_taxonomic_status($status) // only Legitimate should pass through here
    {
        if($status == "Legitimate") return "valid";
        else echo "\n investigate: status undefined [$status]\n";
    }
    
    private function get_parent_info($rec)
    {
        if(!in_array(trim($rec["p"]), array("-", "?", "")))
        {
            $parentNameUsageID  = "";
            $parent_status      = "";
            if($parent = $rec["p"])
            {
                if    ($val = @$this->name_id[$parent]["Legitimate"]["t"])
                {
                    $parentNameUsageID = $val;
                    $parent_status = @$this->name_id[$parent]["Legitimate"]["ns"];
                }
                elseif($val = @$this->name_id[$parent]["Orthographic variant"]["t"])
                {
                    $parentNameUsageID = $val;
                    $parent_status = @$this->name_id[$parent]["Orthographic variant"]["ns"];
                }
                elseif($val = @$this->name_id[$parent]["Invalid"]["t"])
                {
                    $parentNameUsageID = $val;
                    $parent_status = @$this->name_id[$parent]["Invalid"]["ns"];
                }
                elseif($val = @$this->name_id[$parent]["Illegitimate"]["t"])
                {
                    $parentNameUsageID = $val;
                    $parent_status = @$this->name_id[$parent]["Illegitimate"]["ns"];
                    
                }
                elseif($val = @$this->name_id[$parent]["Uncertain"]["t"])
                {
                    $parentNameUsageID = $val;
                    $parent_status = @$this->name_id[$parent]["Uncertain"]["ns"];
                }
                elseif($val = @$this->name_id[$parent]["Unavailable"]["t"])
                {
                    $parentNameUsageID = $val;
                    $parent_status = @$this->name_id[$parent]["Unavailable"]["ns"];
                }
                return array("parentNameUsageID" => $parentNameUsageID, "parent_status" => $parent_status);
            }
            if(!$parentNameUsageID)
            {
                // for checking
                $this->names_not_yet_entered[$parent] = 1;
                $this->no_entry_parent[$parent] = 1;
            }
        }
    }
    
    private function prepare_synonyms()
    {
        foreach($this->name_id as $sciname => $record)
        {
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
                if(isset($this->taxa[$synonym->taxonID])) // this means that a Legitimate taxon has already been entered
                {
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
            }
            else
            {   //  decided not to add a synonym if it has no entry
                $this->names_not_yet_entered[$rec["Name"]] = 1;
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

        // use this to divide 3 batches
        // $l = "A,B,C,D,E,F,G,H";          // 1st batch
        // $l = "I,J,K,L,M,N,O,P,Q";        // 2nd
        // $l = "R,S,T,U,V,W,X,Y,Z";        // 3rd
        // $letters1 = explode(",", $l);
        
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
        
        /* debug - a utility, commented in real operation
        $params = array();
        $letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
        $letters1 = self::get_names_no_entry_from_partner("names_with_error");
        $letters2 = explode(",", $letters);
        foreach($letters1 as $L1)
        {
            foreach($letters2 as $L2)
            {
                $term = ucfirst(strtolower("$L1$L2"));
                $params[] = "$term";
            }
        }
        return $params;
        */

        /* debug - a utility, commented in real operation
        $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/names_not_yet_entered.txt";
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        $contents = utf8_encode($contents);
        fclose($READ);
        $params = json_decode($contents, true);
        return $params;
        */
    }
    
    private function retrieve_names_searched($dump_file = false)
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
        $dump_file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/" . $type . ".txt";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }

    private function sleep_now($results)
    {
        if($results > 1000) sleep(20);
        elseif($results > 500) sleep(15);
        elseif($results > 250) sleep(10);
        elseif($results > 100) sleep(5);
        elseif($results > 50) sleep(5);
        elseif($results > 25) sleep(2);
        elseif($results > 0) sleep(1);
        elseif($results == 0) usleep(500000);
    }

    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

    private function save_to_dump($data, $filename)
    {
        $WRITE = fopen($filename, "a");
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }

    private function initialize_dump_file()
    {
        $WRITE = fopen($this->dump_file, "w");
        fclose($WRITE);
    }

    // private function fill_up_missing_name($name)
    // {
    //     echo "\n manually entered: [$name]";
    //     $this->name_id[$name]["a"] = "";
    //     $this->name_id[$name]["p"] = "";
    //     $this->name_id[$name]["s"] = "";
    //     $this->name_id[$name]["t"] = str_replace(" ", "_", $name);
    //     $this->name_id[$name]["y"] = "";
    //     $this->name_id[$name]["e3"] = "";
    //     $this->name_id[$name]["e4"] = "";
    //     $this->name_id[$name]["cn"] = "";
    //     $this->name_id[$name]["r"]  = "";
    //     $this->name_id[$name]["nt"] = "";
    //     $this->name_id[$name]["ns"] = "";
    //     $this->name_id[$name]["so"] = "";
    //     $this->name_id[$name]["sf"] = "";
    //     $taxon = new \eol_schema\Taxon();
    //     $taxon->taxonID         = str_replace(" ", "_", $name);
    //     $taxon->scientificName  = $name;
    //     if(!isset($this->taxon_ids[$taxon->taxonID]))
    //     {
    //         $this->archive_builder->write_object_to_file($taxon);
    //         $this->taxon_ids[$taxon->taxonID] = 1;
    //     }
    // }

}
?>