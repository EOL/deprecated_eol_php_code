<?php
namespace eol_schema;

class Association extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = "https://dl.dropboxusercontent.com/u/1355101/ontology/association_extension.xml";
    const ROW_TYPE = "http://eol.org/schema/Association";

    public static function validation_rules()
    {
        static $rules = array();
        if(!$rules)
        {
            // these rules apply to individual fields
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/taxonID',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Associations must have taxonIDs'));

            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://eol.org/schema/associationType',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Associations must have associationTypes'));

            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://eol.org/schema/targetTaxonID',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Associations must have targetTaxonIDs'));
        }
        return $rules;
    }
}

?>
