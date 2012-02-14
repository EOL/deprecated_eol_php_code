<?php
namespace eol_schema;

class MediaResource extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = "http://localhost:3000/schema/new_media_extension.xml";
    const ROW_TYPE = "http://eol.org/schema/media/Document";
    
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
                'failure_message'       => 'Media must have identifiers'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/language',
                'validation_function'   => 'eol_schema\MediaResource::valid_language',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid language'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/type',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'DataType must be present'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/type',
                'validation_function'   => 'eol_schema\MediaResource::valid_data_type',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid DataType'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/audubon_core/subtype',
                'validation_function'   => 'eol_schema\MediaResource::valid_data_subtype',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid Data SubType'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm',
                'validation_function'   => 'eol_schema\MediaResource::valid_subject',
                'failure_type'          => 'warning',
                'failure_message'       => 'Unrecognized Subject'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://ns.adobe.com/xap/1.0/rights/UsageTerms',
                'validation_function'   => 'eol_schema\MediaResource::valid_license',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid license'));
                
            // these rules apply to entire rows
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\MediaResource::images_need_urls',
                'failure_type'          => 'error',
                'failure_message'       => 'Images must have an accessURI'));
            
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\MediaResource::text_needs_descriptions',
                'failure_type'          => 'error',
                'failure_message'       => 'Text must have descriptions'));
                
                
        }
        return $rules;
    }
    
    public static function valid_license($v)
    {
        if($v && !preg_match("/^http:\/\/creativecommons.org\/licenses\/(by|by-nc|by-sa|by-nc-sa|publicdomain)\/(1\.0|2\.0|2\.5|3\.0)\/$/i", $v) && $v != 'not applicable')
        {
            return false;
        }
        return true;
    }
    
    public static function valid_language($v)
    {
        if($v && !preg_match("/^[a-z]{2,3}(-[a-z]{2,3})?$/i", $v))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_data_type($v)
    {
        if(preg_match("/^http:\/\/purl\.org\/dc\/dcmitype\/(.*)$/", $v, $arr)) $v = $arr[1];
        if($v && !in_array($v, array(
            'MovingImage',
            'Sound',
            'StillImage',
            'Text'
            )))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_data_subtype($v)
    {
        if($v && !in_array($v, array(
            'Map')))
        {
            return false;
        }
        return true;
    }

    public static function valid_subject($v)
    {
        if(preg_match("/^http:\/\/rs\.tdwg\.org\/ontology\/voc\/SPMInfoItems#(.*)$/", $v, $arr)) $v = $arr[1];
        if($v && !in_array($v, array(
            'Associations',
            'Behaviour',
            'Biology',
            'Conservation',
            'ConservationStatus',
            'Cyclicity',
            'Cytology',
            'Description',
            'DiagnosticDescription',
            'Diseases',
            'Dispersal',
            'Distribution',
            'Ecology',
            'Evolution',
            'GeneralDescription',
            'Genetics',
            'Growth',
            'Habitat',
            'Key',
            'Legislation',
            'LifeCycle',
            'LifeExpectancy',
            'LookAlikes',
            'Management',
            'Migration',
            'MolecularBiology',
            'Morphology',
            'Physiology',
            'PopulationBiology',
            'Procedures',
            'Reproduction',
            'RiskStatement',
            'Size',
            'TaxonBiology',
            'Threats',
            'Trends',
            'TrophicStrategy',
            'Uses')))
        {
            return false;
        }
        return true;
    }
    
    public static function images_need_urls($fields)
    {
        if(@$fields['http://purl.org/dc/terms/type'] == 'http://purl.org/dc/dcmitype/StillImage' &&
            @!$fields['http://rs.tdwg.org/ac/terms/accessURI'])
        {
            return false;
        }
        return true;
    }
    
    public static function text_needs_descriptions($fields)
    {
        if(@$fields['http://purl.org/dc/terms/type'] == 'http://purl.org/dc/dcmitype/Text' &&
            @!$fields['http://purl.org/dc/terms/description'])
        {
            return false;
        }
        return true;
    }
}

?>