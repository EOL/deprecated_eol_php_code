<?php
namespace eol_schema;

class Event extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = "https://dl.dropboxusercontent.com/u/1355101/ontology/event_extension.xml";
    const ROW_TYPE = "http://rs.tdwg.org/dwc/terms/Event";
    const PRIMARY_KEY = "http://rs.tdwg.org/dwc/terms/eventID";
    const GRAPH_NAME = "events";

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
