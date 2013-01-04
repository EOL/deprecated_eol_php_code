<?php
namespace eol_schema;

class DarwinCoreExtensionBase
{
    const EXTENSION_URL = NULL;
    const ROW_TYPE = NULL;
    
    public function __construct($parameters = array())
    {
        if(!isset($GLOBALS['DarwinCoreExtensionProperties'])) $GLOBALS['DarwinCoreExtensionProperties'] = array();
        $this->load_extension();
        $this->assign_properties($parameters);
        
        if(!@$this->extension_row_type && static::ROW_TYPE) $this->extension_row_type = static::ROW_TYPE;
    }
    
    // to be defined by extending classes
    public static function validate_by_hash(&$fields)
    {
        $errors = array();
        $rules = static::validation_rules();
        // get the field name and validation rule(s) for that field
        if(isset($rules))
        {
            foreach($rules as $rule)
            {
                if(get_class($rule) == 'eol_schema\ContentArchiveFieldValidationRule')
                {
                    $test_value = NULL;
                    $test_uri = NULL;
                    if(is_array($rule->field_uri))
                    {
                        foreach($rule->field_uri as $field_uri)
                        {
                            if(isset($fields[$field_uri]))
                            {
                                $test_value = $fields[$field_uri];
                                $test_uri = $field_uri;
                            }
                        }
                    }else
                    {
                        $test_value = @$fields[$rule->field_uri];
                        $test_uri = $rule->field_uri;
                    }
                    $success_or_error = $rule->validate($test_value, $test_uri);
                }elseif(get_class($rule) == 'eol_schema\ContentArchiveRowValidationRule')
                {
                    $success_or_error = $rule->validate($fields);
                }
                if(get_parent_class($success_or_error) == 'eol_schema\ContentArchiveErrorBase')
                {
                    $errors[] = $success_or_error;
                }
            }
        }
        return $errors;
    }
    
    public static function validation_rules()
    {
        return array();
    }
    
    protected function assign_properties($parameters)
    {
        while(list($property_name, $value) = each($parameters))
        {
            $this->__set($property_name, $value);
        }
    }
    
    protected function load_extension()
    {
        if(isset($GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties']))
        {
            $this->accepted_properties = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'];
            $this->accepted_properties_by_name = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'];
            $this->accepted_properties_by_uri = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'];
            
        }else
        {
            $this->accepted_properties = array();
            $this->accepted_properties_by_name = array();
            $this->accepted_properties_by_uri = array();
            
            $extension_xml = self::download_extension(static::EXTENSION_URL);
            $xml = simplexml_load_string($extension_xml);
            foreach($xml->property as $p)
            {
                $property = array();
                $property['name'] = (string) @$p['name'];
                $property['namespace'] = (string) @$p['namespace'];
                $property['uri'] = (string) @$p['qualName'];
                $property['group'] = (string) @$p['group'];
                $property['columnLength'] = (string) @$p['columnLength'];
                $property['thesaurus'] = (string) @$p['thesaurus'];
                $property['required'] = (string) @$p['required'];
                
                $this->accepted_properties[] = $property;
                $this->accepted_properties_by_name[$property['name']] = $property;
                $this->accepted_properties_by_uri[$property['uri']] = $property;
            }
            
            if($row_type = $xml['rowType']) $this->extension_row_type = $row_type;
            
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'] = $this->accepted_properties;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'] = $this->accepted_properties_by_name;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'] = $this->accepted_properties_by_uri;
        }
    }
    
    protected static function download_extension($url)
    {
        $cache_location = __DIR__ . "/extension_cache/schema_". md5($url) .".xml";
        if(file_exists($cache_location)) return file_get_contents($cache_location);
        $extension_contents = file_get_contents($url);
        $CACHE = fopen($cache_location, "w+");
        fwrite($CACHE, $extension_contents);
        fclose($CACHE);
        return $extension_contents;
    }
    
    public function assigned_properties()
    {
        $properties = array();
        foreach($this->accepted_properties as $property)
        {
            $name = @$property['name'];
            if(isset($this->$name))
            {
                $properties[] = array('property' => $property, 'value' => $this->$name);
            }
        }
        return $properties;
    }
    
    public function __set($name, $value)
    {
        if($name == "extension_row_type")
        {
            $this->$name = $value;
        }elseif(isset($this->accepted_properties_by_name[$name]))
        {
            $variable_name = $this->accepted_properties_by_name[$name]['name'];
            $this->$variable_name = $value;
        }elseif(isset($this->accepted_properties_by_uri[$name]))
        {
            $variable_name = $this->accepted_properties_by_uri[$name]['name'];
            $this->$variable_name = $value;
        }else
        {
            $class_variable_names = array('accepted_properties', 'accepted_properties_by_name', 'accepted_properties_by_uri');
            if(!in_array($name, $class_variable_names))
            {
                trigger_error("Undefined property `$name` on ". get_class($this) ." as defined by `". static::EXTENSION_URL ."`", E_USER_WARNING);
            }
        }
        
        // default, original action
        $this->$name = $value;
    }
    
    public function __toString()
    {
        $string = get_called_class()."\n(\n";
        foreach($this as $key => $value)
        {
            if(in_array($key, array('accepted_properties', 'accepted_properties_by_name', 'accepted_properties_by_uri'))) continue;
            if(!$value) continue;
            if(@$this->accepted_properties_by_uri[$key] || @!$this->accepted_properties_by_name[$key])
            {
                $string .= "\t[$key] => $value\n";
            }
        }
        $string .= ")\n";
        return $string;
    }
}

?>