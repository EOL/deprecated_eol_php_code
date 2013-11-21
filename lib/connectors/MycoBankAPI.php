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
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->taxon_ids = array();

        $this->service = 'http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=NameStatus_="Legitimate" AND Name STARTSWITH ';
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->dump_file = $this->TEMP_DIR . "mycobank_dump.txt";
        $this->names_not_yet_entered_dump_file = $this->TEMP_DIR . "names_not_yet_entered.txt";
        $this->names_no_entry_from_partner_dump_file = $this->TEMP_DIR . "names_no_entry_from_partner.txt";
        
        /* not implemented
        $this->dump_file_raw_classification = $this->TEMP_DIR . "raw_classification.txt";
        $this->raw_classification = array();
        */
        
        $this->name_id = array();
        $this->names_not_yet_entered = array();
        $this->no_entry_parent = array();
        $this->no_entry_current_name = array();
        
        /*
        http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000000161&limit=0&filter=NameStatus_="Legitimate" AND Name STARTSWITH "Montagnula perforans"
        http://www.mycobank.org/BioloMICS.aspx?Link=T&amp;Table=Mycobank&amp;Rec=424975&amp;Fields=All
        old: 412911 before Aug19
        new: 395841

        to be reported to partner:
        Cryptococcus, Bensingtonia,  Ceratocystis Dendrothele Penicillium vi, Corticium am -- xml explodes
        */
    }

    function get_all_taxa()
    {
        echo "\n[$this->TEMP_DIR]\n";

        /* to be used if you already have generated dump files.
        $file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
        $this->retrieve_data_and_save_to_array($file);
        // obsolete
        // $file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/raw_classification.txt";
        // $this->process_raw_classification($file);
        */
        
        // /* to be used if you HAVE NOT yet generated dump files
        $this->save_data_to_text(); //exit;
        $this->retrieve_data_and_save_to_array();
        // $this->process_raw_classification(); obsolete, we will no longer add higher taxa from classification, these taxa should have been added in the main call.
        // */

        // $this->remove_taxon_for_illegitimate_parent(); // may not be needed anymore
        // $this->check_if_all_parents_have_entries(); // a utility; check if all perents have its own taxon entry, can proceed without it

        $this->create_instances_from_taxon_object();
        $this->create_archive();
        
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
        
        // stats
        $this->names_not_yet_entered = array_keys($this->names_not_yet_entered);
        echo "\n names_not_yet_entered: " . count($this->names_not_yet_entered);
        self::save_to_dump($this->names_not_yet_entered, $this->names_not_yet_entered_dump_file);

        echo "\n\n no entry parent: " . count($this->no_entry_parent);
        echo "\n no entry current: " . count($this->no_entry_current_name) . "\n";
        
        $temp = array_merge(array_keys($this->no_entry_parent), array_keys($this->no_entry_current_name));
        $temp = array_unique($temp);
        echo "\n total: " . count($temp) . "\n";
    }

    private function get_strings_already_searched($dump_file = false)
    {
        $dump_file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/mycobank_dump.txt";
        $strings_searched = array();
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $records = json_decode($line, true);
                foreach($records as $key => $recs) // $key is the string param used in the search
                {
                    $strings_searched[$key] = "";
                }
            }
        }
        return $strings_searched;
    }
    
    private function get_names_no_entry_from_partner()
    {
        $names = array();
        $dump_file = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/names_no_entry_from_partner.txt";
        foreach(new FileIterator($dump_file) as $line_number => $line)
        {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }
    
    private function save_data_to_text($params = false)
    {
        $names_no_entry_from_partner = self::get_names_no_entry_from_partner();
        $strings_searched = self::get_strings_already_searched();
        if(!$params)
        {
            $params = self::get_params_for_webservice();
            // $params = array("Aspergillus");  //debug
        }
        
        /* debug - a utility, commented in real operation
        $filename = "/Users/eolit/Sites/eli/eol_php_code/tmp/mycobank/names_not_yet_entered.txt";
        $READ = fopen($filename, "r");
        $contents = fread($READ, filesize($filename));
        fclose($READ);
        $params = json_decode($contents, true);
        */
        
        $total_params = count($params);
        self::initialize_dump_file();
        $i = 0;
        foreach($params as $param)
        {
            $i++;
            $no_of_results = 0;
            $param = ucfirst(strtolower($param));
            
            if(in_array($param, $names_no_entry_from_partner)) continue;    // [$param] -> name_no_entry_from_partner";
            if(isset($strings_searched[$param])) continue;                  // [$param] -> was already searched";
            
            $url = $this->service . '"' . $param . '"';
            echo "\n[$param] $i of $total_params \n";

            if($response = Functions::get_hashed_response($url, array('timeout' => 3600, 'download_attempts' => 1, 'delay_in_minutes' => 2))) // 6hrs timeout
            {
                if(isset($response->Error->ErrorMessage))
                {
                    echo "\n investigate error [$param]: " . $response->Error->ErrorMessage . "\n";
                    sleep(300); // 5mins
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
                            // $this->raw_classification[$hierarchy] = ""; not implemented
                            $parent = self::get_parent_from_hierarchy($hierarchy);
                        }
                        if(preg_match("/href\='(.*?)'/ims", $rec->Classification_, $arr)) $source_url = $arr[1];
                        $records[] = array("n"  => (string) $rec->Name,
                                           "cn" => (string) $rec->CurrentName_Pt_,
                                           "r"  => (string) $rec->Rank_Pt_,
                                           "nt" => (string) $rec->NameType_,
                                           "ns" => (string) $rec->NameStatus_,
                                           "a"  => (string) $rec->Authors_,
                                           "p"  => $parent,
                                           "s"  => $source_url,
                                           "t"  => (string) $rec->MycoBankNr_,
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
                    self::save_to_dump($param, $this->names_no_entry_from_partner_dump_file);
                }
            }
            self::sleep_now($no_of_results);
        }
        // self::save_to_dump(array_keys($this->raw_classification), $this->dump_file_raw_classification); not implemented
    }

    private function get_pt_value($string, $return = false)
    {
        /*
        <Rank_Pt_><TargetRecord><Id>20</Id><Name>sp.</Name></TargetRecord></Rank_Pt_>
        [CurrentName_Pt_] => <TargetRecord><Id>46034</Id><Name>Aspergillus zhaoqingensis</Name></TargetRecord>
        <ObligateSynonyms_Pt_><TargetRecord><Id>404707</Id><Name>Aspergillus</Name></TargetRecord></ObligateSynonyms_Pt_>
        <FacultativeSynonyms_Pt_>
            <TargetRecord><Id>99120</Id><Name>Acmosporium</Name></TargetRecord>
            <TargetRecord><Id>99201</Id><Name>Alliospora</Name></TargetRecord>
            ...
        </FacultativeSynonyms_Pt_>
        */
        $records = array();
        if(preg_match_all("/<TargetRecord>(.*?)<\/TargetRecord>/ims", $string, $arr))
        {
            foreach($arr[1] as $line)
            {
                $id = ""; $name = "";
                // if(preg_match("/<Id>(.*?)<\/Id>/ims", $line, $arr2)) $id = trim($arr2[1]); - this is not the correct Id
                if(preg_match("/<Name>(.*?)<\/Name>/ims", $line, $arr2))
                {
                    $name = trim($arr2[1]);
                    $records[] = array("Id" => "", "Name" => $name);
                }
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
                foreach($records as $key => $recs) // $key is the string param used in the search
                {
                    foreach($recs as $rec)
                    {
                        $rec = array_map('trim', $rec);
                        $name = (string) $rec["n"];
                        if(!$name) continue;
                        if(!isset($this->name_id[$name]))
                        {
                            $this->name_id[$name]["a"] = $rec["a"];
                            $this->name_id[$name]["p"] = $rec["p"];
                            $this->name_id[$name]["s"] = $rec["s"];
                            $this->name_id[$name]["t"] = $rec["t"];
                            $this->name_id[$name]["y"] = $rec["y"];
                            // good source for taxon remarks
                            $this->name_id[$name]["e3"] = $rec["e3"];
                            $this->name_id[$name]["e4"] = $rec["e4"];
                            $this->name_id[$name]["cn"] = $rec["cn"];
                            $this->name_id[$name]["r"]  = $rec["r"];
                            $this->name_id[$name]["nt"] = $rec["nt"];
                            $this->name_id[$name]["so"] = $rec["so"];
                            $this->name_id[$name]["sf"] = $rec["sf"];
                        }
                    }
                }
            }
        }
    }
    
    private function remove_taxon_for_illegitimate_parent() // you will only run this during real-time, not when developing; bec when developing ur working with just a subset of the entire taxa
    {
        /* This will remove taxa with illegitimate parent, even if the taxa itself is legitimate. 
           DATA-863:  Nathan Wilson added a comment - 20/Aug/13 7:52 AM
           Another possibility/option is to just remove the parent information if parent is illegitimate and leave the taxon, but we're not doing that here. 
        */
        echo "\n\n orig count: " . count($this->name_id);
        $to_be_removed = array();
        foreach($this->name_id as $taxon => $value)
        {
            $parent = (string) trim($value["p"]);
            if(!in_array($parent, array("-", "?")))
            {
                if(!isset($this->name_id[$parent]))
                {
                    $this->no_entry_parent[$parent] = 1;
                    $to_be_removed[$taxon] = "";
                    // for checking
                    $genus_part = explode(" ", $parent);
                    $genus_part = trim($genus_part[0] . " " . substr(@$genus_part[1], 0, 2));
                    $this->names_not_yet_entered[$genus_part] = 1;
                }
            }
        }
        $to_be_removed = array_keys($to_be_removed);
        echo "\n to_be_removed: " . count($to_be_removed) . "\n";
        foreach($to_be_removed as $taxon) $this->name_id[$taxon] = null;
        $this->name_id = array_filter($this->name_id); //remove null arrays
        echo "\n\n new count: " . count($this->name_id) . "\n";
        echo "\n\n parent with no entry: " . count($this->names_not_yet_entered) . "\n";
    }
    
    private function check_if_all_parents_have_entries() // a utility
    {
        echo "\n\n start of report: taxa that are 'invalid/illegitimate/wrong' \n";
        $invalid_species = array();
        $invalid_higher_taxa = array();
        foreach($this->name_id as $taxon => $value)
        {
            $parent = (string) trim($value["p"]);
            if(!in_array($parent, array("-", "?")))
            {
                if(!isset($this->name_id[$parent]))
                {
                    // echo "\n investigate: $taxon [$parent] no entry for this parent taxon";
                    $parent = trim($parent);
                    $this->no_entry_parent[$parent] = 1;
                    $parts = explode(" ", $parent);
                    if(count($parts) > 1)   $invalid_species[$parent] = 1;
                    else                    $invalid_higher_taxa[$parent] = 1;
                    $this->names_not_yet_entered[$parent] = 1;
                }
            }
        }
        echo "\n count of invalid species: " . count($invalid_species);
        echo "\n count of invalid higher taxa: " . count($invalid_higher_taxa) . "\n";
        echo "\n\n - end of report -\n";
    }

    private function fill_up_missing_name($name)
    {
        $this->name_id[$name]["a"] = "";
        $this->name_id[$name]["p"] = "";
        $this->name_id[$name]["s"] = "";
        $this->name_id[$name]["t"] = str_replace(" ", "_", $name);
        $this->name_id[$name]["y"] = "";
        $this->name_id[$name]["e3"] = "";
        $this->name_id[$name]["e4"] = "";
        $this->name_id[$name]["cn"] = "";
        $this->name_id[$name]["r"]  = "";
        $this->name_id[$name]["nt"] = "";
        $this->name_id[$name]["so"] = "";
        $this->name_id[$name]["sf"] = "";
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = str_replace(" ", "_", $name);
        $taxon->scientificName  = $name;
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
    }
    
    function create_instances_from_taxon_object() //names_not_yet_entered
    {
        foreach($this->name_id as $sciname => $rec)
        {
            $parent = "";
            $taxon_id = "";
            $parentNameUsageID = "";
            
            if(!$taxon_id = @$rec["t"])
            {
                echo "\n investigate: no taxon_id for [$sciname]";
                continue;
            }
            if(in_array($sciname, array("-", "?"))) continue;

            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = (string) $taxon_id;
            $taxon->scientificName              = $sciname;
            $taxon->scientificNameAuthorship    = (string) @$rec["a"];
            $taxon->taxonRank                   = self::get_rank($rec["r"], $sciname);
            $taxon->furtherInformationURL       = (string) @$rec["s"];
            $taxon->taxonRemarks                = self::get_taxon_remark($rec);
            
            if(!in_array(trim($rec["p"]), array("-", "?", "")))
            {
                if($parent = $rec["p"]) $parentNameUsageID = @$this->name_id[$parent]["t"];
                if(!$parentNameUsageID)
                {
                    // /* enable this once there is a complete acquisition of names using the main call/routine
                    $parentNameUsageID = str_replace(" ", "_", $parent);
                    self::fill_up_missing_name($parent);
                    // for checking
                    $genus_part = explode(" ", $parent);
                    $genus_part = trim($genus_part[0] . " " . substr(@$genus_part[1], 0, 2));
                    $this->names_not_yet_entered[$genus_part] = 1;
                    $this->no_entry_parent[$genus_part] = 1;
                    // */
                }
                if(!in_array(trim($parentNameUsageID), array("-", "?", ""))) $taxon->parentNameUsageID = $parentNameUsageID;
            }
            
            $current_name = self::get_pt_value($rec["cn"], "Name"); // current name
            if($sciname != $current_name && $current_name != "")
            {
                if($current_name_id = @$this->name_id[$current_name]["t"]) $taxon->acceptedNameUsageID = $current_name_id;
                else
                {
                    $taxon->acceptedNameUsageID = str_replace(" ", "_", $current_name);
                    self::fill_up_missing_name($current_name);
                    $this->no_entry_current_name[$current_name] = 1;
                    $this->names_not_yet_entered[$current_name] = 1;
                }
            }

            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxa[$taxon->taxonID] = $taxon;
                $this->taxon_ids[$taxon->taxonID] = 1;
            }
            else
            {
                if(!isset($this->taxa[$taxon->taxonID])) continue; //no need to investigate, meaning this might be a synonym used/entered already
                echo "\n investigate 03 [$sciname] \n"; // this means that a 'legitimate' taxon using this id is already entered, acceptable case when checked
                print_r($rec);
                print_r($this->taxa[$taxon->taxonID]);
            }
            self::create_synonyms($rec["sf"], $taxon->taxonID, "Facultative synonym of $sciname");
            self::create_synonyms($rec["so"], $taxon->taxonID, "Obligate synonym of $sciname");
        }
    }

    private function create_synonyms($records, $taxon_id, $taxon_remarks)
    {
        $records = self::get_pt_value($records);
        foreach($records as $rec)
        {
            if($syn_id = @$this->name_id[$rec["Name"]]["t"])
            {
                $synonym = new \eol_schema\Taxon();
                $synonym->taxonID                       = $syn_id;
                $synonym->scientificName                = (string) $rec["Name"];
                $synonym->scientificNameAuthorship      = "";
                $synonym->taxonRank                     = "";
                $synonym->acceptedNameUsageID           = (string) $taxon_id;
                $synonym->taxonomicStatus               = (string) "synonym";
                $synonym->taxonRemarks                  = (string) $taxon_remarks;
                if(!$synonym->scientificName) continue;
                if(!isset($this->taxon_ids[$synonym->taxonID]))
                {
                    $this->archive_builder->write_object_to_file($synonym);
                    $this->taxon_ids[$synonym->taxonID] = 1;
                    $this->syn_count++;
                }
            }
            else
            {
                /* decided not to add a synonym if it has no entry
                $synonym->taxonID = str_replace(" ", "_", $rec["Name"]);
                // for checking
                $genus_part = explode(" ", $rec["Name"]);
                $genus_part = trim($genus_part[0] . " " . substr(@$genus_part[1], 0, 2));
                $this->names_not_yet_entered[$genus_part] = 1;
                echo "\n investigate synonym [" . $rec["Name"] . "] [" . $genus_part . "] is not in this->name_id yet \n";
                */
                continue;
            }
        }
    }

    private function get_taxon_remark($rec)
    {
        $rem = "";
        if($rec["y"])  $rem .= "Name year: " . $rec["y"] . "<br>";
        if($rec["nt"]) $rem .= "Name type: " . $rec["nt"] . "<br>";
        if($rec["e3"]) $rem .= "<br>" . $rec["e3"] . "<br>";
        if($rec["e4"]) $rem .= "<br>" . $rec["e4"] . "<br>";
        return $rem;
    }
    
    private function get_rank($rank, $sciname)
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

    private function get_params_for_webservice()
    {
        $params = array();
        $letters = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
        $letters1 = explode(",", $letters);
        $letters2 = $letters1;
        $letters3 = $letters1;
        foreach($letters1 as $L1)
        {
            foreach($letters2 as $L2)
            {
                foreach($letters3 as $L3)
                {
                    $params[] = "$L1$L2$L3";
                }
            }
        }
        return $params;
    }

    private function get_parent_from_hierarchy($hierarchy)
    {
        $hierarchy = explode(",", $hierarchy);
        return trim(array_pop($hierarchy));
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(TRUE);
    }

    // private function process_raw_classification($filename = false)
    // {
    //     if(!$filename) $filename = $this->dump_file_raw_classification;
    //     $READ = fopen($filename, "r");
    //     $contents = fread($READ, filesize($filename));
    //     fclose($READ);
    //     $hierarchies = json_decode($contents, true);
    //     print_r($hierarchies);
    //     foreach($hierarchies as $hierarchy)
    //     {
    //         $names = explode(",", $hierarchy);
    //         $names = array_map('trim', $names);
    //         $i = 0;
    //         foreach($names as $name)
    //         {
    //             if(!isset($this->name_id[$name]))
    //             {
    //                 $this->name_id[$name]["p"] = @$names[$i-1];
    //             }
    //             $i++;
    //         }
    //     }
    //     print_r($this->name_id);
    // }

}
?>