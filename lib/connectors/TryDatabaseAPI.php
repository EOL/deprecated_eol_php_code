<?php
namespace php_active_record;
/* connector: [430] 
This can be a generic connector for CSV DwCA resources.
*/
class TryDatabaseAPI
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = "http://localhost/cp/TRY/tryv.aug15.zip";
        $this->debug = array();
    }
    private function start()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "TRY_taxa.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        /* Please take note of the weird filename format. 3 different word separator: space, underscore and dash  */
        $tables['process_reference'] = 'TRY reference map.csv';
        $tables['measurements']      = 'TRY_measurements.csv';
        $tables['references']        = 'TRY_references.csv';
        $tables['taxa']              = 'TRY_taxa.csv';
        $tables['occurrence']        = 'TRY-occurrences.csv';
        return array("temp_dir" => $temp_dir, "tables" => $tables);
    }
    function convert_archive()
    {
        if(!($info = self::start())) return;    //uncomment in real operation
        /* only during development so to skip the zip-extracting portion.
        $info = Array("temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_55451/",
                      "tables"   => Array(
                            "process_reference" => "TRY reference map.csv", //the one needs massaging...
                            "measurements"      => "TRY_measurements.csv",
                            "references"        => "TRY_references.csv",
                            "taxa"              => "TRY_taxa.csv",
                            "occurrence"        => "TRY-occurrences.csv"
                        )
        );
        */
        
        print_r($info);
        $temp_dir = $info['temp_dir'];
        $tables = $info['tables'];
        echo "\nConverting TRY dbase CSV archive to EOL DwCA...\n";
        foreach($tables as $class => $filename) {
            self::process_extension($class, $temp_dir.$filename);
        }
        $this->archive_builder->finalize(TRUE);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
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
    private function process_extension($class, $csv_file)
    {
        $ids = array(); //for validation, prevent duplicate identifiers
        $do_ids = array(); //for validation, prevent duplicate identifiers
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            if(    $class == "references")      $c = new \eol_schema\Reference();
            elseif($class == "taxa")            $c = new \eol_schema\Taxon();
            elseif($class == "occurrence")      $c = new \eol_schema\Occurrence();
            elseif($class == "measurements")    $c = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific()
            $row = fgetcsv($file);
            // print_r($row); exit;
            // $row = self::clean_html($row); may not need this anymore...
            // print_r($row);
            
            $i++; if(($i % 20000) == 0) echo "\n $i ";
            // if($i > 2000) break; //debug only - process a subset first 2k
            
            if($i == 1) {
                $fields = self::format_fields($row);
                $count = count($fields);
                // print_r($fields); break; //debug
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                // print_r($fields); print_r($rec); exit;
                
                //start process_reference massaging -----------------------------------------
                if($class == "process_reference") {
                    /*
                    Array(
                        [measurementType] => http://top-thesaurus.org/annotationInfo?viz=1&&trait=Stem_specific_density
                        [occurrenceID] => TRY30_Tilia cordata
                        [RefID] => 290
                    )*/
                    $this->ref_list[md5($rec['measurementType'].$rec['occurrenceID'])][] = $rec['RefID'];
                }
                //end process_reference massaging -----------------------------------------
                
                //start process record =============================================================================================
                /* not needed
                if($class == 'document') {
                    if($rec['taxonID'] && $rec['accessURI']) {
                        if(!Functions::valid_uri_url($rec['accessURI'])) continue;
                        if(!Functions::valid_uri_url($rec['thumbnailURL'])) $rec['thumbnailURL'] = "";

                        $do_id = $rec['identifier'];
                        if(in_array($do_id, $do_ids)) {
                            // exit("\nduplicate do_id [$do_id]\n"); //debug
                            continue;
                        }
                        else $do_ids[] = $do_id;
                    }
                }
                */

                if($class == 'taxa') {
                    $rec['scientificName'] = utf8_encode($rec['scientificName']);
                }
                elseif($class == 'measurements') {
                    if(!$rec['measurementType']) continue;
                    if(!$rec['measurementValue']) continue;
                }
                
                /* Now added as its own columns in measurements, thus this line is now commented.
                $array2 = array('meanlog10', 'SDlog10', 'SampleSize'); //for measurements
                */
                $array2 = array();
                $tfields = array_diff($fields, $array2);
                
                // print_r($tbl); exit;
                foreach($tfields as $field) {
                    // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName" or "wgs84_pos#lat"
                    $parts = explode("#", $field);
                    if($parts[0]) $field = $parts[0];
                    if(@$parts[1]) $field = $parts[1];
                    if($class != "process_reference") $c->$field = $rec[$field];
                }
                if($class == "measurements") {
                    $c->measurementID = Functions::generate_measurementID($c, "try");
                }
                //end process record =============================================================================================


                if($class == 'taxa') {
                    if(isset($ids[$rec['taxonID']])) continue;
                    else $ids[$rec['taxonID']] = '';
                }
                elseif($class == 'measurements') {
                    if(isset($ids[$c->measurementID])) continue;
                    else $ids[$c->measurementID] = '';
                    
                    //start assigning of referenceID from massaged data
                    $md5 = md5($rec['measurementType'].$rec['occurrenceID']);
                    if($val = @$this->ref_list[$md5]) $rec['referenceID'] = implode("; ", $val);
                    //end
                    
                }
                elseif($class == 'occurrence') {
                    if(isset($ids[$rec['occurrenceID']])) continue;
                    else $ids[$rec['occurrenceID']] = '';
                }
                elseif($class == 'references') {
                    if(isset($ids[$rec['identifier']])) continue;
                    else $ids[$rec['identifier']] = '';
                }
                // print_r($rec); exit;
            } //main records
            
            // 'meanlog10', 'SDlog10', 'SampleSize'
            
            if(isset($c)) $this->archive_builder->write_object_to_file($c); //needs if(isset()) here because 'process_reference' is data massaging not archive writing.
            
            // if($i > 100000) break; //debug
            
        } //main loop
        fclose($file);
    }
    private function format_fields($row)
    {
        foreach($row as $col) {
            $final[] = pathinfo($col, PATHINFO_BASENAME);
        }
        return $final;
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
