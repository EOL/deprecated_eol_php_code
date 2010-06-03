<?php

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
            }else $namespace = $GLOBALS['DarwinCoreTaxonDefaultNamespace'];
            
            // find namespace uri in our accepted list
            if(isset($GLOBALS['DarwinCoreTaxonNamespaces'][$namespace_uri]))
            {
                $namespace_abbreviation =& $GLOBALS['DarwinCoreTaxonNamespaces'][$namespace_uri];
                
                // find attribute in list of elements from the namespace
                if(isset($GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri][strtolower($element)]))
                {
                    // set the value of this attribute, or override it if it already is set
                    $attribute =& $GLOBALS['DarwinCoreTaxonNamespaceAttributes'][$namespace_uri][strtolower($element)];
                    if($namespace_uri == $GLOBALS['DarwinCoreTaxonDefaultNamespace']) $this->$attribute = $value;
                    else
                    {
                        // the element is not from the DWN namespace, so create a new object
                        // in this instance using the namespace abbreviation
                        if(!isset($this->$namespace_abbreviation)) $this->$namespace_abbreviation = new stdClass;
                        $this->$namespace_abbreviation->$attribute = $value;
                    }
                }
                //else throw new Exception("Unknown element $element in $namespace");
            }
            //else throw new Exception("Unknown namespace $namespace");
        }
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
