<?php
namespace php_active_record;

class dwc_validator_controller extends ControllerBase
{
    public static function index($parameters)
    {
        // echo "<pre>====="; print_r($parameters); echo "=====</pre>"; //good debug
        
        extract($parameters);
        
        $errors = array();
        $eol_errors = array();
        $eol_warnings = array();
        $stats = array();
        if(!isset($format)) $format = 'html';
        
        $dwca_file = @trim($file_url);
        $suffix = null;
        if(@$dwca_upload['tmp_name']) {
            $dwca_file = $dwca_upload['tmp_name'];
            if(preg_match("/\.([^\.]+)$/", $dwca_upload['name'], $arr)) $suffix = strtolower(trim($arr[1]));
        }
        if($dwca_file) {
            $validation_hash = ContentArchiveValidator::validate_url($dwca_file, $suffix);
            $errors = isset($validation_hash['errors']) ? $validation_hash['errors'] : null;
            $structural_errors = isset($validation_hash['structural_errors']) ? $validation_hash['structural_errors'] : null;
            $warnings = isset($validation_hash['warnings']) ? $validation_hash['warnings'] : null;
            $stats = isset($validation_hash['stats']) ? $validation_hash['stats'] : null;
        }
        if($format == 'json') {
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            $json = array();
            if($structural_errors) $json['status'] = 'invalid';
            elseif($errors) $json['status'] = 'partially valid';
            else $json['status'] = 'valid';
            dwc_validator_controller::add_errors_to_json($structural_errors, $json, 'errors');
            dwc_validator_controller::add_errors_to_json($errors, $json, 'errors');
            dwc_validator_controller::add_errors_to_json($warnings, $json, 'warnings');
            $json['stats'] = $stats;
            echo json_encode($json);
            return;
        }
        else {
            
            $final = array("file_url"           => @$file_url, 
                           "file_upload"        => @$dwca_upload['name'], 
                           "errors"             => @$errors, 
                           "structural_errors"  => @$structural_errors, 
                           "warnings"           => @$warnings, 
                           "stats"              => $stats);
                                                     
            if(isset($parameters['from_jenkins']))
            {
                // print_r($final);
                /* Array (
                    [file_url] => 
                    [file_upload] => 723_bolds.tar.gz
                    [errors] => Array
                        (
                        )
                    [structural_errors] => Array
                        (
                        )
                    [warnings] => Array
                        (
                        )
                    [stats] => Array
                        (
                            [http://rs.tdwg.org/dwc/terms/measurementorfact] => Array
                                (
                                    [Total] => 17629
                                )
                            [http://rs.tdwg.org/dwc/terms/taxon] => Array
                                (
                                    [Total] => 9
                                )
                        )
                )
                */
                self::show_results($final);
                unlink($parameters['dwca_upload']['tmp_name']);
                return;
            }
            render_template("validator/index", $final);
        }
    }
    private static function show_results($p)
    {
        if(stripos($p['file_upload'], "xls") !== false) {} //string is found
        else echo "<br>"; //for some reason there are extra lines for spreadsheets.
        
        echo "You uploaded: <b>$p[file_upload]</b><br>";
        echo "<b>";
        if(@$p['errors'] || @$p['structural_errors']) echo "With errors";
        else
        {
            if(@$p['warnings'] && @$p['stats']) echo "Valid Archive but with Warnings";
            elseif(!@$p['warnings'] && @$p['stats']) echo "Valid Archive";
            else echo "Unknown state";
        }
        echo "</b>";
        
        foreach($p as $topic => $arr) {
            if($p[$topic]) {
                if($arr && is_array($arr)) {
                    echo "<br>----------------------------[$topic]----------------------------";
                    foreach(@$arr as $index => $value) {
                        if(is_array($value)) {
                            echo "<br><b>$index</b>";
                            foreach(@$value as $index2 => $value2) {
                                if(is_array($value2)) {
                                    echo "<br>$index2";
                                    foreach(@$value2 as $index3 => $value3) {
                                        echo "<br>------ $index3 = ".json_encode($value3);
                                    }
                                }
                                else echo "<br>--- $index2 = ".json_encode($value2);
                            }
                        }
                        else
                        {
                            /* echo "<br>$index = ".json_encode($value); */ //orig just display json
                            // 8 = {"file":"taxon.tab","line":230142,"uri":"http:\/\/rs.tdwg.org\/dwc\/terms\/taxonRank","value":"nothomorph","message":"Unrecognized taxon rank"}
                            // print_r($value);
                            if($json = json_encode($value)) {
                                if($json_a = json_decode($json, true)) {
                                    foreach($json_a as $ind => $val) {
                                        echo "<br>-- $ind => ".json_encode($val);
                                    }
                                }
                            }
                            echo "<br>";
                        }
                    }
                    echo "<br>--------------------------------------------------------" . str_repeat("-", strlen($topic)+2) . "";
                }
            }
            // else echo "<hr>No $topic<hr>";
        }
        echo " <a href='index.php'>Back to main</a><br>";
    }
    private static function add_errors_to_json($errors, &$json, $index)
    {
        if($errors) {
            if(!isset($json[$index])) $json[$index] = array();
            foreach($errors as $error) {
                 $error_hash = (array) $error;
                 if(isset($error_hash['line'])) {
                     $error_hash['lines'] = explode(",", str_replace(" ", "", $error_hash['line']));
                     unset($error_hash['line']);
                 }
                 $json[$index][] = $error_hash;
            }
        }
    }
}

?>
