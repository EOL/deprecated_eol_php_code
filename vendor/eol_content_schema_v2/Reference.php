<?php
namespace eol_schema;

class Reference extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = "http://eol.org/schema/reference_extension.xml";
    const ROW_TYPE = "http://eol.org/schema/reference/Reference";
    
    public static function validation_rules()
    {
        static $rules = array();
        return $rules;
    }
}

?>