<?php
namespace php_active_record;

class DarwinCoreTaxon
{
    public function __construct($parameters = array())
    {
        foreach($parameters as $element => $value)
        {
            if(preg_match("/^(.*\/)(.*?)$/i", trim($element), $arr))
            {
                $namespace_uri = trim($arr[1]);
                $element = trim($arr[2]);
            }elseif(preg_match("/^([a-z_]{1,20}):(.*?)$/i", trim($element), $arr))
            {
                $namespace_abbreviation = trim($arr[1]);
                $element = trim($arr[2]);
                $namespace_uri = $GLOBALS['DarwinCoreTaxonDefaultNamespace'];
                
                if(isset($GLOBALS['DarwinCoreTaxonNamespaceAbbreviations'][$namespace_abbreviation]))
                {
                    $namespace_uri = $GLOBALS['DarwinCoreTaxonNamespaceAbbreviations'][$namespace_abbreviation];
                }else
                {
                    // trigger_error("DarwinCoreTaxon: Unknown namespace $namespace_abbreviation", E_USER_NOTICE);
                    continue;
                }
            }else $namespace_uri = $GLOBALS['DarwinCoreTaxonDefaultNamespace'];
            
            // find namespace uri in our accepted list
            if(isset($GLOBALS['DarwinCoreTaxonNamespaces'][$namespace_uri]))
            {
                $namespace_abbreviation = $GLOBALS['DarwinCoreTaxonNamespaces'][$namespace_uri];
                
                // find attribute in list of elements from the namespace
                if(isset($GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri][strtolower($element)]))
                {
                    // set the value of this attribute, or override it if it already is set
                    $attribute = $GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri][strtolower($element)];
                    if($namespace_uri == $GLOBALS['DarwinCoreTaxonDefaultNamespace']) $this->$attribute = $value;
                    else
                    {
                        // the element is not from the DWN namespace, so create a new object
                        // in this instance using the namespace abbreviation
                        if(!isset($this->$namespace_abbreviation)) $this->$namespace_abbreviation = new \stdClass;
                        $this->$namespace_abbreviation->$attribute = $value;
                    }
                }
                // else trigger_error("DarwinCoreTaxon: Unknown element $element in $namespace_uri", E_USER_NOTICE);
            }
            // else trigger_error("DarwinCoreTaxon: Unknown namespace $namespace", E_USER_NOTICE);
        }
    }
    
    public function __toString()
    {
        return Functions::print_r_public($this, true);
    }
    
    public function __toXML()
    {
        $default_ns = $GLOBALS['DarwinCoreTaxonNamespaces'][$GLOBALS['DarwinCoreTaxonDefaultNamespace']];
        $xml =  "<$default_ns:Taxon>\n";
        
        foreach($this as $property => $value)
        {
            if(is_object($value))
            {
                $namespace = $property;
                foreach($value as $p => $v)
                {
                    $tag = $namespace .":". $p;
                    $xml .= "  <$tag>".htmlspecialchars($v)."</$tag>\n";
                }
            }elseif(is_array($value))
            {
                // // vernacular names can be
                // // array("name1", "name2")
                // // array("lang" => "name") or
                // // array("lang" => array("name1", "name2"))
                if($property == 'vernacularName')
                {
                    $tag = $default_ns .":". $property;
                    foreach($value as $lang => $vern)
                    {
                        if(is_array($vern))
                        {
                            foreach($vern as $k => $v)
                            {
                                if($lang && is_string($lang)) $xml .= "  <$tag xml:lang='".htmlspecialchars($lang)."'>".htmlspecialchars($v)."</$tag>\n";
                                else $xml .= "  <$tag>".htmlspecialchars($v)."</$tag>\n";
                            }
                        }
                        elseif($lang && is_string($lang)) $xml .= "  <$tag xml:lang='".htmlspecialchars($lang)."'>".htmlspecialchars($vern)."</$tag>\n";
                        else $xml .= "  <$tag>".htmlspecialchars($vern)."</$tag>\n";
                    }
                }else
                {
                    $tag = $default_ns .":". $property;
                    foreach($value as $k => $v)
                    {
                        $xml .= "  <$tag>".htmlspecialchars($v)."</$tag>\n";
                    }
                }
            }else
            {
                $tag = $default_ns .":". $property;
                $xml .= "  <$tag>".htmlspecialchars($value)."</$tag>\n";
            }
        }
        
        $xml .= "</$default_ns:Taxon>\n";
        return $xml;
    }
}

?>
