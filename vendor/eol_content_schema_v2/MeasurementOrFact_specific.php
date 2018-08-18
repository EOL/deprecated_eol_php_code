<?php
namespace eol_schema;

class MeasurementOrFact extends DarwinCoreExtensionBase
{
    // const EXTENSION_URL = "https://dl.dropboxusercontent.com/u/1355101/ontology/measurement_extension.xml";
    // const EXTENSION_URL = "http://editors.eol.org/other_files/ontology/measurement_extension.xml";
    const EXTENSION_URL = "http://localhost/cp/TRY/measurement_extension.xml";
    
    const ROW_TYPE = "http://rs.tdwg.org/dwc/terms/MeasurementOrFact";
    const PRIMARY_KEY = "http://rs.tdwg.org/dwc/terms/measurementID";
    const GRAPH_NAME = "measurements";

    public static function validation_rules()
    {
        static $rules = array();
        if(!$rules)
        {
            // these rules apply to individual fields
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/measurementType',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'MeasurementOrFacts must have measurementTypes'));

            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/measurementValue',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'MeasurementOrFacts must have measurementValues'));
        }
        return $rules;
    }
}

?>
