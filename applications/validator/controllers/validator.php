<?php

class validator_controller extends ControllerBase
{
    public static function index($parameters)
    {
        extract($parameters);
        
        $errors = array();
        $eol_errors = array();
        $eol_warnings = array();
        
        
        $xml_file = @$file_url;
        if(@$xml_upload['tmp_name']) $xml_file = $xml_upload['tmp_name'];
        
        if($xml_file)
        {
            $validator = new SchemaValidator();
            
            // determine the xsd, and whether this is an EOL resource file
            $xsd = $validator->get_schema_location($xml_file);
            if(preg_match("/^http:\/\/services\.eol\.org\/schema\/content_/", $xsd)) $is_eol_schema = true;
            else $is_eol_schema = false;
            
            $valid = $validator->validate($xml_file);
            
            if($valid !== true)
            {
                $errors = $valid;
            }elseif(@$is_eol_schema)
            {
                // only do this extra processing on files of 40000KB (~39MB) and less
                $file_size = Functions::remote_file_size($xml_file);
                if($file_size && $file_size<40000) list($eol_errors, $eol_warnings) = SchemaParser::eol_schema_validate($xml_file);
                else $is_eol_schema = false;
            }
        }
        
        @render_template("validator/index", array("file_url" => $file_url, "file_upload" => $xml_upload['name'], "is_eol_schema" => $is_eol_schema, "xsd" => $xsd, "errors" => $errors, "eol_errors" => $eol_errors, "eol_warnings" => $eol_warnings));
    }
}

?>