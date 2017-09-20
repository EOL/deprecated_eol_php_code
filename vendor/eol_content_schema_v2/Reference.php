<?php
namespace eol_schema;

class Reference extends DarwinCoreExtensionBase
{
    // const EXTENSION_URL = "http://eol.org/schema/reference_extension.xml";
    const EXTENSION_URL = "http://editors.eol.org/other_files/ontology/reference_extension.xml";
    
    const ROW_TYPE = "http://eol.org/schema/reference/Reference";
    const PRIMARY_KEY = "http://purl.org/dc/terms/identifier";
    const GRAPH_NAME = "references";
    
    public static function validation_rules()
    {
        static $rules = array();
        if(!$rules)
        {
            // these rules apply to individual fields
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/identifier',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'References must have identifiers'));
            
            // these rules apply to entire rows
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\Reference::references_need_titles',
                'failure_type'          => 'error',
                'failure_message'       => 'References must minimally contain a full_reference or title'));
        }
        return $rules;
    }
    
    public static function references_need_titles($fields)
    {
        if(@!$fields['http://eol.org/schema/reference/full_reference'] && 
           @!$fields['http://eol.org/schema/reference/primaryTitle'] && 
           @!$fields['http://purl.org/dc/terms/title'])
        {
            return false;
        }
        return true;
    }
}

?>
