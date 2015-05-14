<?php
namespace php_active_record;

class SchemaValidator
{ 
    public static function validate($uri, $only_well_formedness = false)
    {
        if(!$uri) return false;
        // try to find the XSD and fail if it cannot
        if($xsd = self::get_schema_location($uri)) $schema_location = $xsd;
        else return array("There was no XSD defined in this XML file");
        
        // we have had problems in the past with Services being unavailable, so
        // instead of checking there just use the local schemas which are the same
        if($schema_location == 'http://services.eol.org/schema/content_0_1.xsd')
        {
            $schema_location = WEB_ROOT . 'applications/schema/content_0_1.xsd';
        }
        if($schema_location == 'http://services.eol.org/schema/content_0_2.xsd')
        {
            $schema_location = WEB_ROOT . 'applications/schema/content_0_2.xsd';
        }
        if($schema_location == 'http://services.eol.org/schema/content_0_3.xsd')
        {
            $schema_location = WEB_ROOT . 'applications/schema/content_0_3.xsd';
        }
        if($schema_location == 'http://services.eol.org/schema/content_0_3_18.xsd')
        {
            $schema_location = WEB_ROOT . 'applications/schema/content_0_3_18.xsd';
        }
        if($schema_location == 'http://services.eol.org/schema/content_0_4.xsd')
        {
            $schema_location = WEB_ROOT . 'applications/schema/content_0_4.xsd';
        }
        if($schema_location == 'http://services.eol.org/schema/content_1_0.xsd')
        {
            $schema_location = WEB_ROOT . 'applications/schema/content_1_0.xsd';
        }
        
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $reader = new \XMLReader();
        $reader->open($uri, 'utf8');
        if(!$only_well_formedness)
        {
            if(@!$reader->setSchema($schema_location))
            {
            	write_to_resource_harvesting_log("The specified schema could not be loaded or contained errors: $schema_location");
            	return array("The specified schema could not be loaded or contained errors: $schema_location");
            }
        }
        libxml_clear_errors();
        
        while(@$reader->read())
        {
            // empty loop to load errors into libxml error cache
            
            //if($reader->name == "#text") echo $reader->name .":". $reader->value."\n";
            // if(libxml_get_errors())
            // {
            //     echo libxml_get_last_error()->message."\n";
            //     libxml_clear_errors();
            // }
        }
        
        if($errors = self::get_errors())
        {
        	write_to_resource_harvesting_log(implode(",", $errors));
        	return $errors;
        }
        return true;
    }
    
    public static function get_schema_location($uri)
    {
        if(!($FILE = @fopen($uri, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $uri);
          return;
        }
        
        // number of lines of the XML file to look for xsi:schemaLocation in
        $n = 30;
        $first_n_lines = "";
        
        $i = 0;
        while($FILE && !feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $first_n_lines .= " ".trim($line)." ";
                $i++;
                if($i>=$n) break;
            }
        }
        
        if(preg_match("/xsi:schemaLocation=('|\")(.*?)\\1/i", $first_n_lines, $arr))
        {
            $schema_location = trim($arr[2]);
            if(preg_match("/^(.*?) (.*)$/", $schema_location, $arr)) return trim($arr[2]);
            return $schema_location;
        }
        return false;
    }
    
    private static function libxml_error_array($error)
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
        
        return $error_array;
    }
    
    private static function get_errors_array()
    {
        $return = array();
        
        $errors = libxml_get_errors();
        foreach($errors as $error)
        {
            if($array = self::libxml_error_array($error)) $return[] = $array;
        }
        libxml_clear_errors();
        
        return $return;
    }
    
    private static function get_errors()
    {
        $validation_result = self::get_errors_array();
        $errors = array();
        foreach($validation_result as $k => $v)
        {
            $error_string = $v["type"]." ".$v["code"].": ".$v["message"]." on line ".$v["line_number"];
            if($line = $v["line"]) $error_string .= ": ".htmlspecialchars($line);
            $errors[] = $error_string;
        }
        
        return $errors;
    }
}

?>
