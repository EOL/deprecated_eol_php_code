<?php
namespace eol_schema;

class Occurrence extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = "https://dl.dropboxusercontent.com/u/1355101/ontology/occurrence_extension.xml";
    const ROW_TYPE = "http://rs.tdwg.org/dwc/terms/Occurrence";

    public static function validation_rules()
    {
        static $rules = array();
        if(!$rules)
        {

        }
        return $rules;
    }
}

?>
