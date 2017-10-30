<?php
namespace php_active_record;
/* Darwin core branch extractor - TRAM-583
Processes any DwCA taxon extension (taxon.tab/txt/tsv).
*/
class DwCA_Branch_Extractor
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        $this->debug = array();
        
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",
                                  //start of other row_types:
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  );
                                  /*
                                  [1] => http://rs.gbif.org/terms/1.0/speciesprofile
                                  [6] => http://rs.gbif.org/terms/1.0/typesandspecimen
                                  [7] => http://rs.gbif.org/terms/1.0/distribution
                                  */
    }

    private function start()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => true)); //true means it will re-download, will not use cache. Set TRUE when developing
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
    
    private function build_id_name_array($records)
    {
        foreach($records as $rec) {
            // [tID] => 6de0dc42e8f4fc2610cb4287a4505764
            // [sN] => Accipiter cirrocephalus rosselianus Mayr, 1940
            $taxon_id = (string) $rec["tID"];
            $this->id_name[$taxon_id]['sN'] = (string) $rec["sN"];
            $this->id_name[$taxon_id]['pID'] = (string) $rec["pID"];
        }
    }
    
    /*
    private function generate_higherClassification_field($records)
    {
        e.g. $rec
        Array
            [tID] => 5e2712849c197671c260f53809836273
            [sN] => Passerina leclancherii leclancherii Lafresnaye, 1840
            [pID] => 49fc924007e33cc43908fed677d5499a
        $i = 0;
        foreach($records as $rec) {
            $higherClassification = self::get_higherClassification($rec);
            $records[$i]["hC"] = $higherClassification; //assign value to main $records -> UNCOMMENT in real operation
            $i++;
        }
        return $records;
    }
    private function get_higherClassification($rek)
    {
        $parent_id = $rek['pID'];
        $str = "";
        while($parent_id) {
            if($parent_id) {
                $str .= Functions::canonical_form(trim(@$this->id_name[$parent_id]['sN']))."|";
                $parent_id = @$this->id_name[$parent_id]['pID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1); // echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr); // echo "\n new: [$str]\n";
        return $str;
    }
    */
    
    private function can_compute_higherClassification($single_rec)
    {
        if(!isset($single_rec["tID"])) return false;
        if(!isset($single_rec["sN"])) return false;
        if(!isset($single_rec["pID"])) return false;
        return true;
    }
    //ends here 
    
    private function extract_branch($taxon_id)
    {
        echo "taxon_id: [$taxon_id]";
        print_r($this->id_name[$taxon_id]);
        /*
        [sN] => Struthio camelus Linnaeus, 1758
        [pID] => cc26d87dc5a15502a9d00af428f93101
        [anak] => Array
            (
                [0] => 8b0bb34c6485a0a791818a2b93cc7212
                [1] => 7288a29751c0ffc544c7fe2cf9dbac4b
                [2] => 3a3dbb738053311b6dfff3ced501cb85
                [3] => 70cd71beb6615b4bcf2cc4d0004739ac
            ) */
        $parent_id = $this->id_name[$taxon_id]['pID'];
        $upwards = self::get_ancestry_upwards($parent_id);
        // print_r($upwards);
        /*
        echo "<hr>upwards:<br>";
        foreach($upwards as $taxon_id)
        {
            echo "taxon is $taxon_id ";
            print_r($this->id_name[$taxon_id]);
        }
        */
        
        $downwards = self::get_ancestry_downwards($taxon_id);
        // print_r($downwards);
        echo "<br>total upwards: ".count($upwards);
        echo "<br>total downwards: ".count($downwards);
        
        exit("<hr>");
    }
    private function get_ancestry_downwards($sought_taxon_id)
    {
        $final = array(); $children = array();

        //1st step get all immediate children
        if($children[1] = self::get_immediate_children($sought_taxon_id)) $final = array_merge($final, $children[1]);
        else return array();

        //2nd step loop onwards:
        foreach(@$children[1] as $taxon_id)
        {
            if($children[2] = self::get_immediate_children($taxon_id)) $final = array_merge($final, $children[2]);
            else continue;
            foreach(@$children[2] as $taxon_id2)
            {
                if($children[3] = self::get_immediate_children($taxon_id2)) $final = array_merge($final, $children[3]);
                else continue;
                foreach(@$children[3] as $taxon_id3)
                {
                    if($children[4] = self::get_immediate_children($taxon_id3)) $final = array_merge($final, $children[4]);
                    else continue;
                    foreach(@$children[4] as $taxon_id4)
                    {
                        if($children[5] = self::get_immediate_children($taxon_id4)) $final = array_merge($final, $children[5]);
                        else continue;
                        foreach(@$children[5] as $taxon_id5)
                        {
                            if($children[6] = self::get_immediate_children($taxon_id5)) $final = array_merge($final, $children[6]);
                            else continue;
                            foreach(@$children[6] as $taxon_id6)
                            {
                                if($children[7] = self::get_immediate_children($taxon_id6)) $final = array_merge($final, $children[7]);
                                else continue;
                                foreach(@$children[7] as $taxon_id7)
                                {
                                    if($children[8] = self::get_immediate_children($taxon_id7)) $final = array_merge($final, $children[8]);
                                    else continue;
                                    foreach(@$children[8] as $taxon_id8)
                                    {
                                        if($children[9] = self::get_immediate_children($taxon_id8)) $final = array_merge($final, $children[9]);
                                        else continue;
                                        foreach(@$children[9] as $taxon_id9)
                                        {
                                            if($children[10] = self::get_immediate_children($taxon_id9)) $final = array_merge($final, $children[10]);
                                            else continue;
                                            
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $indexes = array_keys($children);
        print_r($indexes);
        echo "<br>Reached level: ". end($indexes);
        
        // wrong last step
        // //last step: append all $children
        // $final = array();
        // foreach($children as $index => $value) {
        //     if($arr = @$children[$index]) $final = array_merge($final, $arr);
        // }
        // echo "<hr>total downwards [$index level]: ".count($final)."<hr>";

        return $final;
    }
    private function get_immediate_children($sought_taxon_id)
    {
        return @$this->id_name[$sought_taxon_id]['anak'];
        /* veeeery long to process
        $children = array();
        foreach($this->id_name as $taxon_id => $item) {
            if($sought_taxon_id == $item['pID']) {
                // print_r($this->id_name[$taxon_id]);
                $children[] = (string) $taxon_id;
            }
        }
        return $children;
        */
    }
    private function get_ancestry_upwards($parent_id)
    {
        $parent_ids = array();
        $parent_ids[] = (string) $parent_id; //first taxon
        while($parent_id) {
            if($parent_id) {
                if($parent_id = @$this->id_name[$parent_id]['pID']) $parent_ids[] = (string) $parent_id;
            }
        }
        return $parent_ids;
    }
    //=====================================================================================================================
    //start functions for the interface tool
    //=====================================================================================================================
    function tool_generate_higherClassification($file)
    {
        if(self::create_records_array($file)) {
            // $records = self::extract_branch("ceab0b65522ca514b497c009eb60c834");     //species
            // $records = self::extract_branch("cc26d87dc5a15502a9d00af428f93101");     //genus
            // $records = self::extract_branch("e86ef0e503d2961a3da298cea6da8021");     //family
            // $records = self::extract_branch("eea14f4c044d251bf3ee9ee99417c91f");     //order
            // $records = self::extract_branch("6168a5808fb28ee5581c52a1994b97ab");     //top node
            
            //dwh_taxa.txt
            // $records = self::extract_branch("4807313");     //viruses
            // $records = self::extract_branch("-2");     //order
            $records = self::extract_branch("805080");     //top node

            
            
            
            

            //start write to file
            $fields = self::normalize_fields($records[0]);
            if(!($f = Functions::file_open(str_replace("sample/", "temp/", $file), "w"))) return;
            fwrite($f, implode("\t", $fields)."\n");
            foreach($records as $rec) fwrite($f, implode("\t", $rec)."\n");
            fclose($f);
            return true;
        }
        else return false;
    }
    
    private function create_records_array($file)
    {
        // echo "\n[$file]\n";
        // $records = array();
        $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++;
            if($i == 1) {
                $fields = explode("\t", $row);
                /* original scheme
                // this is for specific resource criteria
                if($file == "sample/GBIF_Taxon.tsv") //https://eol-jira.bibalex.org/browse/TRAM-552
                {
                    //fields for GBIF taxa - we tried to limit the no. of fields due to big size of file
                    $fieldz = array("taxonID", "parentNameUsageID", "acceptedNameUsageID", "scientificName", "scientificNameAuthorship", "specificEpithet", "infraspecificEpithet", "taxonRank", "taxonomicStatus");
                    $fieldz = array("taxonID", "parentNameUsageID", "acceptedNameUsageID", "scientificName", "taxonRank", "taxonomicStatus");
                }
                elseif($file == "sample/dwh_taxa.txt") //https://eol-jira.bibalex.org/browse/TRAM-575
                {
                    $fieldz = array("taxonID", "acceptedNameUsageID", "parentNameUsageID", "scientificName", "taxonRank", "source", "taxonomicStatus");
                    $fieldz = array("taxonID", "acceptedNameUsageID", "parentNameUsageID", "scientificName", "taxonRank", "taxonomicStatus"); //worked OK
                }
                elseif($file == "something else") {}
                else $fieldz = $fields; //no criteria needed, for normal operation
                */
                
                //became default for all resources as of 29-Oct-2017
                $fieldz = array();
                $proposed = array("taxonID", "acceptedNameUsageID", "parentNameUsageID", "scientificName", "taxonRank", "taxonomicStatus"); 
                foreach($proposed as $p) {
                    if(in_array($p, $fields)) $fieldz[] = $p;
                }
                
            }
            else {
                $rec = array();
                $cols = explode("\t", $row);
                $k = 0;
                foreach($fields as $field) {
                    $short_field = self::shorten_field($field);
                    if(in_array($field, $fieldz)) $rec[$short_field] = @$cols[$k];
                    $k++;
                }
                if($rec) {
                    // this is for specific resource criteria
                    if($file == "sample/GBIF_Taxon.tsv") { //https://eol-jira.bibalex.org/browse/TRAM-552
                        if($rec['tS'] != 'accepted') continue;
                    }
                    elseif($file == "sample/dwh_taxa.txt") { //https://eol-jira.bibalex.org/browse/TRAM-575
                        if($rec['tS'] != 'accepted') continue;
                    }

                    // $records[] = $rec;
                    /* start build_id_name_array()
                    [tID] => 6de0dc42e8f4fc2610cb4287a4505764
                    [sN] => Accipiter cirrocephalus rosselianus Mayr, 1940
                    */
                    if($taxon_id = (string) $rec["tID"])
                    {
                        $this->id_name[$taxon_id]['sN'] = (string) $rec["sN"];
                        $this->id_name[$taxon_id]['pID'] = (string) $rec["pID"];
                        $this->id_name[$rec["pID"]]['anak'][] = $taxon_id;
                    }
                    
                    if($i > 3 && $i <= 10) { //can check this early if we can compute for higherClassification, used a range so it will NOT check for every record but just 7 records.
                        if(!self::can_compute_higherClassification($rec)) return false;
                    }
                    
                }
            }
        }
        // return $records;
        return true;
    }
    
    private function normalize_fields($arr)
    {
        $fields = array_keys($arr);
        $k = 0;
        foreach($fields as $field) {
            $fields[$k] = self::lengthen_field($field);
            $k++;
        }
        return $fields;
    }

    private function shorten_field($field)
    {
        switch ($field) {
            case "taxonID":             return "tID"; break;
            case "parentNameUsageID":   return "pID"; break;
            case "acceptedNameUsageID": return "aID"; break;
            case "scientificName":      return "sN"; break;
            case "taxonRank":           return "tR"; break;
            case "taxonomicStatus":     return "tS"; break;
            case "taxonRemarks":        return "tRe"; break;
            case "source":              return "s"; break;
            default: //exit("\nundefined field\n");
        }
    }

    private function lengthen_field($field)
    {
        switch ($field) {
            case "tID": return "taxonID"; break;
            case "pID": return "parentNameUsageID"; break;
            case "aID": return "acceptedNameUsageID"; break;
            case "sN":  return "scientificName"; break;
            case "tR":  return "taxonRank"; break;
            case "tS":  return "taxonomicStatus"; break;
            case "hC":  return "higherClassification"; break;
            case "tRe": return "taxonRemarks"; break;
            case "s":   return "source"; break;
            default:
        }
    }

    //=====================================================================================================================
    //end functions for the interface tool "genHigherClass"
    //=====================================================================================================================

}
?>