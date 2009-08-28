<?php

class SchemaValidator
{
    public $lines;
    public $doc;
    public $schema_location;
    
    public function validate($xml_file, $is_eol_schema = true)
    {
        libxml_use_internal_errors(true);
        
        $this->doc = new DOMDocument('1.0', 'utf-8');
        $this->doc->load($xml_file);
        
        if($is_eol_schema) $this->schema_location = LOCAL_ROOT."applications/schema/content_0_2.xsd";
        if($xsd = $this->get_schema_location($xml_file)) $this->schema_location = $xsd;
        if(@!$this->schema_location)
        {
            return array("There was no XSD defined in this XML file");
        }
        
        //$file_contents = @file_get_contents($xml_file);
        //if(!$file_contents) return array("Error: Upload Failed");
        //$this->lines = @explode("\n", $file_contents);
        
        if(@!$this->doc->schemaValidate($this->schema_location))
        {
            unset($this->doc);
            return $this->get_errors();
        }
        
        unset($this->doc);
        return true;
    }
    
    public function get_schema_location($xml_file)
    {
        $schema_location = "";
        
        if(@!$this->doc)
        {
            $this->doc = new DOMDocument();
            $this->doc->load($xml_file);
        }
        
        if($root = $this->doc->documentElement) $schema_location = $root->getAttribute("xsi:schemaLocation");
        if(preg_match("/ (http:\/\/[^ ]+\.xsd)$/", $schema_location, $arr)) $schema_location = $arr[1];
        
        return $schema_location;
    }
    
    public function libxml_error_array($error)
    {
        if($error->code=="1") return "";
        
        $error_array = array();
        $error_array["type"] = "Error";
        switch ($error->level)
        {
            case LIBXML_ERR_WARNING:
                $error_array["type"] = "Warning";
                break;
            case LIBXML_ERR_ERROR:
                $error_array["type"] = "Error";
                break;
            case LIBXML_ERR_FATAL:
                $error_array["type"] = "Fatal Error";
                break;
        }
        
        $error_array["code"] = $error->code;
        $error_array["message"] = trim($error->message);
        $error_array["line_number"] = trim($error->line);
        $error_array["line"] = "";
        //if($error_array["line_number"] != 65535 && @$line_number = $error_array["line_number"]) $error_array["line"] = @$this->lines[$line_number-1];

        return $error_array;
    }
    
    public function get_errors_array()
    {
        $return = array();
        
        $errors = libxml_get_errors();
        foreach ($errors as $error)
        {
            if($array = $this->libxml_error_array($error)) $return[] = $array;
        }
        libxml_clear_errors();
        
        return $return;
    }
    
    public function get_errors()
    {
        $validation_result = $this->get_errors_array();
        $errors = array();
        foreach($validation_result as $k => $v)
        {
            $error_string = "<b>".$v["type"]."</b> ".$v["code"].": ".$v["message"]." on line ".$v["line_number"];
            if($line = $v["line"]) $error_string .= ": ".htmlspecialchars($line);
            $errors[] = $error_string;
        }
        
        //print_r($errors);
        
        return $errors;
    }
    
}

?>