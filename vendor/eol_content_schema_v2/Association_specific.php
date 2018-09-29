<?php
namespace eol_schema;

class Association_specific extends DarwinCoreExtensionBase
{
    // const EXTENSION_URL = "https://editors.eol.org/other_files/ontology/association_extension.xml";
    const EXTENSION_URL = "http://localhost/cp/TRY/association_extension_specific.xml";
    const ROW_TYPE = "http://eol.org/schema/Association";
    const PRIMARY_KEY = "http://eol.org/schema/associationID";
    const GRAPH_NAME = "associations";

    public static function validation_rules()
    {
        static $rules = array();
        if(!$rules)
        {
            // these rules apply to individual fields
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/occurrenceID',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Associations must have occurrenceIDs'));

            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://eol.org/schema/associationType',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Associations must have associationTypes'));

            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://eol.org/schema/targetOccurrenceID',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Associations must have targetOccurrenceIDs'));
        }
        return $rules;
    }
}

?>
