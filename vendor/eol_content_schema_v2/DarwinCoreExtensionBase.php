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
    public static function validate_by_hash(&$fields, $skip_warnings = false)
    {
        $errors = array();
        $rules = static::validation_rules();
        // get the field name and validation rule(s) for that field
        if(isset($rules))
        {
            foreach($rules as $rule)
            {
                if($skip_warnings && $rule->failure_type == 'warning') continue;
                if(get_class($rule) == 'eol_schema\ContentArchiveFieldValidationRule')
                {
                    $test_value = NULL;
                    $test_uri = NULL;
                    if(is_array($rule->field_uri))
                    {
                        $test_data = array();
                        foreach($rule->field_uri as $field_uri)
                        {
                            if(isset($fields[$field_uri]))
                            {
                                $test_data[] = array('value' => $fields[$field_uri], 'uri' => $field_uri);
                            }else $test_data[] = array('value' => NULL, 'uri' => NULL);
                        }
                        usort($test_data, array('\eol_schema\DarwinCoreExtensionBase', 'sort_fields_by_value'));
                        foreach($test_data as $data)
                        {
                            $success_or_error = $rule->validate($data['value'], $data['uri']);
                            if($data['value']) break;
                        }
                    }else
                    {
                        $test_value = @$fields[$rule->field_uri];
                        $test_uri = $rule->field_uri;
                        $success_or_error = $rule->validate($test_value, $test_uri);
                    }
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
            self::add_property(); //per https://eol-jira.bibalex.org/browse/TRAM-499?focusedCommentId=61534&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61534
            
            if($row_type = $xml['rowType']) $this->extension_row_type = $row_type;
            
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'] = $this->accepted_properties;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'] = $this->accepted_properties_by_name;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'] = $this->accepted_properties_by_uri;
        }
    }
    private function add_property() //per https://eol-jira.bibalex.org/browse/TRAM-499?focusedCommentId=61534&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61534
    {
        // /* new May 30, 2019
        $fields = array();
        $fields[] = array('name' => 'canonicalName',    'namespace' => 'http://rs.gbif.org/terms', 'uri' => 'http://rs.gbif.org/terms/1.0/canonicalName');
        $fields[] = array('name' => 'EOLid',            'namespace' => 'http://eol.org/schema',    'uri' => 'http://eol.org/schema/EOLid');
        $fields[] = array('name' => 'EOLidAnnotations', 'namespace' => 'http://eol.org/schema',    'uri' => 'http://eol.org/schema/EOLidAnnotations');
        $fields[] = array('name' => 'Landmark',         'namespace' => 'http://eol.org/schema',    'uri' => 'http://eol.org/schema/Landmark');
        foreach($fields as $f) {
            $property = array();
            $property['name']       = $f['name'];
            $property['namespace']  = $f['namespace'];
            $property['uri']        = $f['uri'];
            $property['group']          = '';
            $property['columnLength']   = '';
            $property['thesaurus']      = '';
            $property['required']       = '';
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
        }
        // */
        
        /* worked OK but only for one field. Used above instead for multiple fields
        $property = array();
        $property['name']       = 'canonicalName';
        $property['namespace']  = 'http://rs.gbif.org/terms';
        $property['uri']        = 'http://rs.gbif.org/terms/1.0/canonicalName';
        $property['group']          = '';
        $property['columnLength']   = '';
        $property['thesaurus']      = '';
        $property['required']       = '';
        $this->accepted_properties[] = $property;
        $this->accepted_properties_by_name[$property['name']] = $property;
        $this->accepted_properties_by_uri[$property['uri']] = $property;
        */
    }
    private function local_file_get_contents($url)
    {
        $context = stream_context_create(
            array("http" => array("header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"))
        );
        return file_get_contents($url, false, $context);
    }
    protected static function download_extension($url)
    {
        $cache_location = __DIR__ . "/extension_cache/schema_". md5($url) .".xml";
        if(file_exists($cache_location)) return self::local_file_get_contents($cache_location);
        $extension_contents = self::local_file_get_contents($url);
        if(!($CACHE = fopen($cache_location, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$cache_location);
          return;
        }
        fwrite($CACHE, $extension_contents);
        fclose($CACHE);
        return $extension_contents;
    }
    
    public function assigned_properties()
    {
        self::add_property(); //per https://eol-jira.bibalex.org/browse/TRAM-499?focusedCommentId=61534&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61534
        
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
        if(!$name) return; //DATA-1733 - 'Shelled_animal_body_mass' by Eli. This means there is/are column(s) in spreadsheet template e.g. under 'measurements or facts' sheet, 4 undefined columns between "Measurement Value" and "Unit". See orig body-size-shells.xlsx
        
        //per https://eol-jira.bibalex.org/browse/TRAM-499?focusedCommentId=61534&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61534
        $this->accepted_properties_by_name['canonicalName'] = array('name' => 'canonicalName', 'namespace' => 'http://rs.gbif.org/terms', 'uri' => 'http://rs.gbif.org/terms/1.0/canonicalName');

        // /* new May 30, 2019
        $this->accepted_properties_by_name['EOLid'] = array('name' => 'EOLid', 'namespace' => 'http://eol.org/schema', 'uri' => 'http://eol.org/schema/EOLid');
        $this->accepted_properties_by_name['EOLidAnnotations'] = array('name' => 'EOLidAnnotations', 'namespace' => 'http://eol.org/schema', 'uri' => 'http://eol.org/schema/EOLidAnnotations');
        $this->accepted_properties_by_name['Landmark'] = array('name' => 'Landmark', 'namespace' => 'http://eol.org/schema', 'uri' => 'http://eol.org/schema/Landmark');
        // */

        /* Not needed anymore, since a specific measurement_extension.xml is available for such resources
        //per TRY database resource:
        $this->accepted_properties_by_name['meanlog10'] = array('name' => 'meanlog10', 'namespace' => 'http://eol.org/schema/terms', 'uri' => 'http://eol.org/schema/terms/meanlog10');
        $this->accepted_properties_by_name['SDlog10'] = array('name' => 'SDlog10', 'namespace' => 'http://eol.org/schema/terms', 'uri' => 'http://eol.org/schema/terms/SDlog10');
        $this->accepted_properties_by_name['SampleSize'] = array('name' => 'SampleSize', 'namespace' => 'http://eol.org/schema/terms', 'uri' => 'http://eol.org/schema/terms/SampleSize');
        */
        
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
    
    public static function sort_fields_by_value($a, $b)
    {
        return ($a['value'] < $b['value']) ? 1 : -1;
    }
}

?>