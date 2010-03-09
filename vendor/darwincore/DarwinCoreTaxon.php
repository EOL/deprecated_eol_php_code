<?php

class DarwinCoreTaxon
{
    private $namespaces;
    private $possible_attributes;
    private $default_namespace;
    private $default_element;
    
    public function __construct($parameters = array())
    {
        $this->namespaces = array();
        $this->possible_attributes = array();
        
        $this->default_namespace = "http://rs.tdwg.org/dwc/terms/";
        $this->default_element = "Taxon";
        $this->load_schema("http://rs.tdwg.org/dwc/terms/", "dwc", dirname(__FILE__)."/tdwg_dwcterms.xsd");
        $this->load_schema("http://purl.org/dc/elements/1.1/", "dc", dirname(__FILE__)."/dc_elements.xsd");
        $this->load_schema("http://purl.org/dc/terms/", "dcterms", dirname(__FILE__)."/dcterms.xsd");
        
        foreach($parameters as $element => $value)
        {
            if(preg_match("/^(.*\/)(.*?)$/i", trim($element), $arr))
            {
                $namespace = trim($arr[1]);
                $element = trim($arr[2]);
            }else $namespace = $this->default_namespace;
            
            // find namespace uri in our accepted list
            $namespace_abbreviation = Functions::array_searchi($namespace, $this->namespaces);
            if($namespace_abbreviation !== null)
            {
                // find attribute in list of elements from the namespace
                $attribute_key = Functions::array_searchi($element, $this->possible_attributes[$namespace_abbreviation]);
                if($attribute_key !== null)
                {
                    // set the value of this attribute, or override it if it already is set
                    $attribute = $this->possible_attributes[$namespace_abbreviation][$attribute_key];
                    if($namespace == $this->default_namespace) $this->$attribute = $value;
                    else $this->$namespace_abbreviation->$attribute = $value;
                }else throw new Exception("Unknown element $element in $namespace");
            }else throw new Exception("Unknown namespace $namespace");
        }
    }
    
    private function load_schema($namespace_uri, $namespace_abbreviation, $schema_uri)
    {
        // cannot reuse namespace
        if(isset($this->namespaces[$namespace_abbreviation])) throw new Exception("Cannot redeclare namespace $namespace_abbreviation");
        if(isset($this->$namespace_abbreviation)) throw new Exception("Object already has attribute with label $namespace_abbreviation");
        // set as viable namespace
        $this->namespaces[$namespace_abbreviation] = $namespace_uri;
        // default elements of the namespace to empty array
        $this->possible_attributes[$namespace_abbreviation] = array();
        // create a new object for this namespace abbreviation if its not the default
        if($namespace_uri != $this->default_namespace) $this->$namespace_abbreviation = new stdClass;
        $this->possible_attributes[$namespace_abbreviation] = self::schema_elements($schema_uri);
    }
    
    private static function schema_elements($schema_uri)
    {
        // this static array will cache the results so schemas will be read only once per script
        static $schema_elements = array();
        if(isset($schema_elements[$schema_uri])) return $schema_elements[$schema_uri];
        
        $elements = array();
        $xml = Functions::get_hashed_response($schema_uri);
        if(!$xml) throw new Exception("Cannot access schema at $schema_uri");
        $xml_schema = $xml->children("http://www.w3.org/2001/XMLSchema");
        foreach($xml_schema->element as $e)
        {
            $attr = $e->attributes();
            $elements[] = (string) $attr['name'];
        }
        $schema_elements[$schema_uri] = $elements;
        return $elements;
    }
    
    public function __toString()
    {
        return Functions::print_r_public($this, true);
    }
    
    public function __toXML()
    {
        // undefined
    }
}

?>
