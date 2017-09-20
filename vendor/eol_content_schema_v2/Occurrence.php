<?php
namespace eol_schema;

class Occurrence extends DarwinCoreExtensionBase
{
    // const EXTENSION_URL = "https://dl.dropboxusercontent.com/u/1355101/ontology/occurrence_extension.xml";
    const EXTENSION_URL = "http://editors.eol.org/other_files/ontology/occurrence_extension.xml";

    const ROW_TYPE = "http://rs.tdwg.org/dwc/terms/Occurrence";
    const PRIMARY_KEY = "http://rs.tdwg.org/dwc/terms/occurrenceID";
    const GRAPH_NAME = "occurrences";

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
