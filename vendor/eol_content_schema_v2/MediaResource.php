<?php
namespace eol_schema;

class MediaResource extends DarwinCoreExtensionBase
{
    // const EXTENSION_URL = "http://eol.org/schema/media_extension.xml";
    const EXTENSION_URL = "http://editors.eol.org/other_files/ontology/media_extension.xml";
    
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
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/taxonID',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'error',
                'failure_message'       => 'Media must have taxonIDs'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/language',
                'validation_function'   => 'eol_schema\MediaResource::valid_language',
                'failure_type'          => 'error',
                'failure_message'       => 'Language should use standardized ISO 639 language codes'));
            
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
                'field_uri'             => array(
                                                'http://ns.adobe.com/xap/1.0/rights/UsageTerms',
                                                'http://purl.org/dc/terms/license'),
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'warning',
                'failure_message'       => 'License should be present'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => array(
                                                'http://ns.adobe.com/xap/1.0/rights/UsageTerms',
                                                'http://purl.org/dc/terms/license'),
                'validation_function'   => 'eol_schema\MediaResource::valid_license',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid license'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/ac/terms/accessURI',
                'validation_function'   => 'eol_schema\MediaResource::valid_url',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid URL'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://eol.org/schema/media/thumbnailURL',
                'validation_function'   => 'eol_schema\MediaResource::valid_url',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid URL'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/ac/terms/furtherInformationURL',
                'validation_function'   => 'eol_schema\MediaResource::valid_url',
                'failure_type'          => 'error',
                'failure_message'       => 'Invalid URL'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/description',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Descriptions should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://purl.org/dc/terms/title',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Titles should be encoded in UTF-8'));
            
            // these rules apply to entire rows
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\MediaResource::media_need_urls',
                'failure_type'          => 'error',
                'failure_message'       => 'Multimedia must have accessURIs'));
            
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\MediaResource::text_needs_descriptions',
                'failure_type'          => 'error',
                'failure_message'       => 'Text must have descriptions'));
            
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\MediaResource::text_needs_subjects',
                'failure_type'          => 'error',
                'failure_message'       => 'Text must have subjects'));
        }
        return $rules;
    }
    
    public static function valid_license($v)
    {
        if($v && !preg_match("/^http:\/\/creativecommons.org\/licen(s|c)es\/((by|by-nc|by-sa|by-nc-sa|publicdomain)\/(1\.0|2\.0|2\.5|3\.0|4\.0)|publicdomain)\/$/i", trim($v)) &&
            strtolower($v) != 'http://creativecommons.org/publicdomain/zero/1.0/' &&
            strtolower($v) != 'http://www.flickr.com/commons/usage/' &&
            strtolower($v) != 'no known copyright restrictions' &&
            strtolower($v) != 'public domain' &&
            strtolower($v) != 'not applicable')
        {
            return false;
        }
        return true;
    }
    
    public static function valid_url($v)
    {
        // must start with http:// and contain at least one dot ( . )
        if($v && !preg_match("/^(https?|ftp):\/\/.*\./i", $v))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_language($v)
    {
        if($v && !preg_match("/^[a-z]{2,3}(-[a-z]{2,5})?$/i", $v))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_data_type($v)
    {
        if(preg_match("/^http:\/\/purl\.org\/dc\/dcmitype\/(.*)$/", strtolower($v), $arr)) $v = $arr[1];
        if($v && !in_array($v, array(
            'movingimage',
            'sound',
            'stillimage',
            'text')))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_data_subtype($v)
    {
        if($v && !in_array(strtolower($v), array(
            'map')))
        {
            return false;
        }
        return true;
    }

    public static function valid_subject($v)
    {
        if(preg_match("/^http:\/\/rs\.tdwg\.org\/ontology\/voc\/SPMInfoItems#(.*)$/i", $v, $arr)) $v = $arr[1];
        elseif(preg_match("/^http:\/\/rs\.tdwg\.org\/pccore\/([a-z]+)$/i", $v, $arr)) $v = $arr[1];
        elseif(preg_match("/^http:\/\/eol\.org\/schema\/eol_info_items\.xml#(.*)$/i", $v, $arr)) $v = $arr[1];
        elseif(preg_match("/^http:\/\/www\.eol\.org\/voc\/table_of_contents#(.*)$/i", $v, $arr)) $v = $arr[1];
        if($v && !in_array(strtolower($v), array(
            'associations',
            'behaviour',
            'biology',
            'conservation',
            'conservationstatus',
            'cyclicity',
            'cytology',
            'description',
            'diagnosticdescription',
            'diseases',
            'dispersal',
            'distribution',
            'ecology',
            'evolution',
            'generaldescription',
            'genetics',
            'growth',
            'habitat',
            'key',
            'legislation',
            'lifecycle',
            'lifeexpectancy',
            'lookalikes',
            'management',
            'migration',
            'molecularbiology',
            'morphology',
            'physiology',
            'populationbiology',
            'procedures',
            'reproduction',
            'riskstatement',
            'size',
            'taxonbiology',
            'threats',
            'trends',
            'trophicstrategy',
            'uses',
            'use',
            
            // PlinianCore terms
            'distribution',
            'abstract',
            'annualcycle',
            'behavior',
            'briefdescription',
            'chromosomicnumbern',
            'conservationstatuscites',
            'conservationstatusuicn',
            'distribution',
            'documenteduses',
            'ecologicalsignificance',
            'endemicity',
            'endemism',
            'feeding',
            'folklore',
            'habit',
            'habitat',
            'identificationkeys',
            'interactions',
            'invasivenessdata',
            'legislation',
            'lifecycle',
            'management',
            'migratorydata',
            'moleculardata',
            'nationallegislation',
            'otherinformationsources',
            'papers',
            'phenology',
            'population',
            'populationbiology',
            'populationstate',
            'publicationdate',
            'regionallegislation',
            'reproduction',
            'scientificdescription',
            'seasons',
            'speciespublicationreference',
            'symbioticrelationship',
            'targetaudiences',
            'territory',
            'threatstatus',
            'traditionaluses',
            'typecollector',
            'typedepository',
            'typelocation',
            'typification',
            'unstructureddocumentation',
            'unstructurednaturalhistory',
            'uses',

            // EOL terms
            'typeinformation',
            'education',
            'barcode',
            'wikipedia',
            'citizenscience',
            'educationresources',
            'genome',
            'nucleotidesequences',
            'gossilhistory',
            'systematicsorphylogenetics',
            'functionaladaptations',
            'development',
            'identificationresources',
            'notes',
            'taxonomy',
            'typeinformation',
            'taxonomy',
            'development')))
        {
            return false;
        }
        return true;
    }
    
    public static function media_need_urls($fields)
    {
        if(in_array(@strtolower($fields['http://purl.org/dc/terms/type']), array(
            'http://purl.org/dc/dcmitype/stillimage',
            'http://purl.org/dc/dcmitype/movingimage',
            'http://purl.org/dc/dcmitype/sound')) &&
            @!$fields['http://rs.tdwg.org/ac/terms/accessURI'])
        {
            return false;
        }
        return true;
    }
    
    public static function text_needs_descriptions($fields)
    {
        if(@strtolower($fields['http://purl.org/dc/terms/type']) == 'http://purl.org/dc/dcmitype/text' &&
            @!$fields['http://purl.org/dc/terms/description'])
        {
            return false;
        }
        return true;
    }
    
    public static function text_needs_subjects($fields)
    {
        if(@strtolower($fields['http://purl.org/dc/terms/type']) == 'http://purl.org/dc/dcmitype/text' &&
            @!$fields['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'])
        {
            return false;
        }
        return true;
    }

    protected function load_extension()
    {
        parent::load_extension();
        // add dc:license
        $property = array();
        $property['name'] = 'license';
        $property['namespace'] = 'http://purl.org/dc/terms';
        $property['uri'] = "http://purl.org/dc/terms/license";
        $this->accepted_properties[] = $property;
        $this->accepted_properties_by_name[$property['name']] = $property;
        $this->accepted_properties_by_uri[$property['uri']] = $property;
    }

}

?>
