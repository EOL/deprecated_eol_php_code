<?php
namespace php_active_record;

class dwc_validator_controller extends ControllerBase
{
    public static function index($parameters)
    {
        print_r($parameters);
        
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
            render_template("validator/index", array("file_url"             => @$file_url, 
                                                     "file_upload"          => @$dwca_upload['name'], 
                                                     "errors"               => @$errors, 
                                                     "structural_errors"    => @$structural_errors, 
                                                     "warnings"             => @$warnings, 
                                                     "stats"                => $stats));
        }
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
