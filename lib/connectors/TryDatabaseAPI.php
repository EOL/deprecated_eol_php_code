<?php
namespace php_active_record;
/* connector: [try.php] 
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
        // $this->dwca_file = "http://localhost/cp/TRY/tryv.aug15.zip";
        $this->dwca_file = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/TRY/tryv.aug15.zip";
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
    private function format_ref_ids($str)
    {
        $str = str_replace(array(0,1,2,3,4,5,6,7,8,9), "", $str);
        $arr = explode(";", $str);
        $arr = array_unique($arr);
        $arr = array_map('trim', $arr);
        sort($arr);
        foreach($arr as $ref_id_2write) $this->ref_ids_2write[$ref_id_2write] = ''; //select only those refs to write to archive
        $str = implode("; ", $arr);
        return $str;
    }
    function convert_archive()
    {
        /* tests
        $str = "TRY24128;USDA634;MBG849752";
        $str = "MBG8445;MBG8447;MBG8450;MBG8452;MBG8453;MBG8454;MBG8459;MBG8462;MBG8463;MBG8465;MBG8466;MBG8468;MBG8471;MBG8472;MBG8473;MBG8475;MBG8476;MBG8477;MBG8478;MBG8480;MBG8482;MBG8484;MBG8485;MBG8487;MBG8489;MBG8491;MBG8492;MBG8494;MBG8496;MBG8497;MBG8499;MBG8500;MBG8501;MBG8503;MBG8504;MBG8506;MBG8508;MBG8509;MBG8510;MBG8511;MBG8517;MBG8519;MBG8520;MBG8522;MBG8526;MBG8532;MBG8536;MBG8538;MBG8547;MBG8548;MBG8551;MBG8552;MBG8555;MBG8558;MBG8559;MBG8561;MBG8562;MBG8808;MBG8809;MBG8810;MBG8812;MBG8813;MBG8814;MBG8815;MBG8816;MBG8817;MBG8819;MBG8820;MBG8821;MBG8822;MBG8823;MBG8824;MBG8825;MBG8826;MBG8827;MBG8828;MBG8829;MBG8833;MBG8834;MBG8835;MBG8837;MBG8838;MBG8839;MBG8841;MBG8842;MBG9221;MBG9223;MBG9224;MBG9228;MBG9229;MBG9233;MBG9237;MBG9240;MBG9242;MBG9247;MBG9249;MBG9250;MBG9252;MBG9253;MBG9257;MBG9258;MBG9259;MBG9260;MBG9261;MBG9262;MBG9263;MBG9264;MBG9265;MBG9266;MBG9267;MBG9268;MBG9270;MBG9277;MBG9280;MBG9281;MBG9282;MBG9284;MBG9285;MBG9287;MBG9288;MBG9291;MBG9292;MBG9293;MBG9294;MBG9295;MBG9296;MBG9302;MBG9305;MBG9310;MBG9311;MBG9315;MBG9319;MBG9321;MBG9322;MBG9324;MBG9327;MBG9328;MBG9329;MBG9330;MBG9332;MBG9339;MBG9341;MBG9348;MBG9349;MBG9350;MBG9351;MBG9359;MBG9365;MBG9368;MBG9370;MBG9371;MBG9373;MBG9375;MBG9378;MBG9379;MBG9380;MBG9381;MBG9383;MBG9386;MBG9387;MBG9388;MBG9389;MBG9390;MBG9391;MBG9394;MBG9395;MBG9397;JW1106;BB2526";
        $str = "TRY26569;USDA662;USDA671;USDA675;MBG843773;MBG843774;MBG843775;MBG848952;MBG850180;MBG850252;MBG850256;MBG850541;MBG850542;MBG850543;MBG851150;MBG895773;MBG895778;MBG895779;MBG897304;MBG897345";
        // $str = "TRY18991";
        echo "\n$str\n";
        echo "\n".self::format_ref_ids($str)."\n";
        exit("\n-end tests-\n");
        */
        
        if(!($info = self::start())) return;    //uncomment in real operation
        /* only during development so to skip the zip-extracting portion.
        $info = Array("temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/dir_37276/",
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
        recursive_rmdir($temp_dir);  //un-comment in real operation
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
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
            $row = array_map('trim', $row);
            // print_r($row); exit;
            // $row = self::clean_html($row); may not need this anymore...
            // print_r($row);
            
            $i++; if(($i % 20000) == 0) echo "\n $i ($class)";
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
                    // $rec[$field] = $values[$k]; old ways
                    $rec[$field] = Functions::conv_to_utf8($values[$k]);
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($fields); print_r($rec);

                // /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1766?focusedCommentId=62821&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62821
                if($class == "measurements") {
                    /* oooops! This is unrelated except that it's in the same resource. As long as you have the TRY file in front of you, I gave you a typo in the source file. 
                    You'll find a bunch of records where the measurementType and the measurementUniit are both http://purl.obolibrary.org/obo/UO_0000082
                    Please change the measurementType in those records to
                    http://top-thesaurus.org/annotationInfo?viz=1&&trait=Leaflet_lamina_area */
                    if($rec['measurementType'] == "http://purl.obolibrary.org/obo/UO_0000082" &&
                       $rec['measurementUnit'] == "http://purl.obolibrary.org/obo/UO_0000082") $rec['measurementType'] = "http://top-thesaurus.org/annotationInfo?viz=1&&trait=Leaflet_lamina_area";
                }
                // */
                
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
                    
                    if($str = @$rec['referenceID']) { //e.g. "MBG28348;MBG28349"
                        $str = self::format_ref_ids($str);
                        $rec['referenceID'] = $str;
                        $this->debug['refs_in_question'][$str] = ''; //debug - comment in real operation
                    }
                    
                    //start assigning of referenceID from massaged data
                    $md5 = md5($rec['measurementType'].$rec['occurrenceID']);
                    if($val = @$this->ref_list[$md5]) {
                        $val = array_unique($val);
                        sort($val);
                        
                        if($str = $rec['referenceID']) { //if for some reason referenceID is not blank
                            $rec['referenceID'] = $str."; ".implode("; ", $val);
                            $this->debug['refs_concatenated'][$rec['referenceID']] = ''; //debug - comment in real operation
                        }
                        else $rec['referenceID'] = implode("; ", $val);
                        
                        foreach($val as $ref_id_2write) $this->ref_ids_2write[$ref_id_2write] = ''; //select only those refs to write to archive
                        // echo "\nref hit in measurements [".$rec['referenceID']."]\n";
                    }
                    //end
                }
                elseif($class == 'references') {
                    if(!isset($this->ref_ids_2write[$rec['identifier']])) continue;
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
