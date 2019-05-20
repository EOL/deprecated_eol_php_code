<?php
namespace php_active_record;
/* connector: [generate_cmd.php]
              [generate_cmd_gbif.php] - for GBIF backbone pruning
Processes any DwCA taxon extension (taxon.tab/txt/tsv).
Using the parentNameUsageID, generates a new DwCA with a new taxon column: http://rs.tdwg.org/dwc/terms/higherClassification
User Warning: Undefined property `rights` on eol_schema\Taxon as defined by `http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd` in /Library/WebServer/Documents/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 168
*/
class DwCA_Utility_cmd
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

    /* moved inside create_records_array()
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
    */
    
    /* old version, doesn't scale
    private function generate_higherClassification_field($records)
    {   
        // e.g. $rec
        // Array
        //     [tID] => 5e2712849c197671c260f53809836273
        //     [sN] => Passerina leclancherii leclancherii Lafresnaye, 1840
        //     [pID] => 49fc924007e33cc43908fed677d5499a
        $i = 0;
        foreach($records as $rec) {
            $higherClassification = self::get_higherClassification($rec);
            $records[$i]["hC"] = $higherClassification; //assign value to main $records -> UNCOMMENT in real operation
            $i++;
        }
        return $records;
    }
    */
    private function generate_higherClassification_field($file)
    {
        $filename_tmp = str_replace("temp/", "temp/temp_", $file);
        $f = Functions::file_open($filename_tmp, "w");
        $i = 0;
        foreach(new FileIterator($file) as $line => $row) {
            $i++;
            if($i == 1) {
                $fields = explode("\t", $row);
                $fields[] = "higherClassification";
                fwrite($f, implode("\t", $fields)."\n");
                $fieldz = $fields; //no criteria needed, for normal operation
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
                    $higherClassification = self::get_higherClassification($rec);
                    $rec["hC"] = $higherClassification;
                    fwrite($f, implode("\t", $rec)."\n");
                }
            }
        }
        fclose($f);
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

    private function can_compute_higherClassification($single_rec)
    {
        if(!isset($single_rec["tID"])) return false;
        if(!isset($single_rec["sN"])) return false;
        if(!isset($single_rec["pID"])) return false;
        return true;
    }
    
    //ends here 
    
    //=====================================================================================================================
    //start functions for the interface tool "genHigherClass"
    //=====================================================================================================================
    
    function tool_generate_higherClassification($file)
    {
        if(self::create_records_array($file)) {
            self::generate_higherClassification_field($file);
            /* important step: rename from: [temp/temp_1509427898.tab] to: [temp/1509427898.tab] */
            unlink($file);
            //long-cut since Functions::file_rename didn't work
            $filename_tmp = str_replace("temp/", "temp/temp_", $file);
            if(copy($filename_tmp, $file)) unlink($filename_tmp);
            return true;
        }
        else return false;
    }

    private function create_records_array($file)
    {
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
                
                /*
                //became default for all resources as of 29-Oct-2017
                $fieldz = array();
                $proposed = array("taxonID", "acceptedNameUsageID", "parentNameUsageID", "scientificName", "taxonRank", "taxonomicStatus"); 
                foreach($proposed as $p) {
                    if(in_array($p, $fields)) $fieldz[] = $p;
                }
                */
                
                // /* Eli as of May 19, used in DH TRAM-808
                $fieldz = $fields;
                // */
                
                
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
                    
                    //originally from build_id_name_array()
                    $taxon_id = (string) $rec["tID"];
                    $this->id_name[$taxon_id]['sN'] = (string) $rec["sN"];
                    $this->id_name[$taxon_id]['pID'] = (string) $rec["pID"];
                    
                    // this is for specific resource criteria
                    if($file == "sample/GBIF_Taxon.tsv") { //https://eol-jira.bibalex.org/browse/TRAM-552
                        if($rec['tS'] != 'accepted') continue;
                    }
                    elseif($file == "sample/dwh_taxa.txt") { //https://eol-jira.bibalex.org/browse/TRAM-575
                        if($rec['tS'] != 'accepted') continue;
                    }

                    // $records[] = $rec;
                    if($i > 3 && $i <= 10) { //can check this early if we can compute for higherClassification, used a range so it will NOT check for every record but just 7 records.
                        if(!self::can_compute_higherClassification($rec)) return false;
                    }
                    
                }
            }
        }
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
            case "furtherInformationURL":   return "fIU"; break;
            case "datasetID":               return "dID"; break;
            case "canonicalName":           return "cN"; break;
            case "EOLid":                   return "Ei"; break;
            case "EOLidAnnotations":        return "EiA"; break;
            default: exit("\nundefined field [$field]\n");
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
            case "fIU": return "furtherInformationURL"; break;
            case "dID": return "datasetID"; break;
            case "cN":  return "canonicalName"; break;
            case "Ei":  return "EOLid"; break;
            case "EiA": return "EOLidAnnotations"; break;
            default:
        }
    }

    //=====================================================================================================================
    //end functions for the interface tool "genHigherClass"
    //=====================================================================================================================

}
?>