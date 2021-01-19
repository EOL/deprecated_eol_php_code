<?php
namespace eol_schema;

class Agent extends DarwinCoreExtensionBase
{
    // const EXTENSION_URL = "http://eol.org/schema/agent_extension.xml";
    const EXTENSION_URL = "http://editors.eol.org/other_files/ontology/agent_extension.xml";
    
    const ROW_TYPE = "http://eol.org/schema/agent/Agent";
    
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
                'failure_message'       => 'Agents must have identifiers'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://xmlns.com/foaf/spec/#term_logo',
                'validation_function'   => 'eol_schema\MediaResource::valid_url',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid URL'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://eol.org/schema/agent/agentRole',
                'validation_function'   => 'eol_schema\Agent::valid_agent_role',
                'failure_type'          => 'warning',
                'failure_message'       => 'Unrecognized agent role'));
            
            // these rules apply to entire rows
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\Agent::agents_need_names',
                'failure_type'          => 'error',
                'failure_message'       => 'Agents must minimally contain a term_name, term_firstName or term_familyName'));
        }
        return $rules;
    }
    
    public static function valid_agent_role($v)
    {
        if($v && !in_array(strtolower($v), array(
            'animator',
            'author',
            'compiler',
            'composer',
            'creator',
            'director',
            'editor',
            'illustrator',
            'photographer',
            'project',
            'provider',
            'publisher',
            'recorder',
            'source',
            'contributor')))
        {
            return false;
        }
        return true;
    }
    
    public static function agents_need_names($fields)
    {
        if(@!$fields['http://xmlns.com/foaf/spec/#term_name'] && 
           @!$fields['http://xmlns.com/foaf/spec/#term_firstName'] && 
           @!$fields['http://xmlns.com/foaf/spec/#term_familyName'])
        {
            return false;
        }
        return true;
    }
}

?>