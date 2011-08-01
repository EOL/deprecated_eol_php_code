<?php
namespace php_active_record;

class ContentArchiveValidator
{
    public static function validate($uri, $directory)
    {
        if(!$uri && !$directory) return false;
        // try to find the XSD and fail if it cannot
        
        $GLOBALS['ContentArchiveValidatorErrors'] = array();
        $archive = new ContentArchiveReader($uri, $directory);
        foreach($archive->tables as $row_type => $table)
        {
            if($new_errors = $archive->process_table($row_type, "php_active_record\\ContentArchiveValidator::validate_row"))
            {
                $errors = array_merge($errors, $new_errors);
            }
        }
        
        if($GLOBALS['ContentArchiveValidatorErrors']) return $GLOBALS['ContentArchiveValidatorErrors'];
        return true;
    }
    
    public static function validate_row($row)
    {
        foreach($row as $uri => $value)
        {
            if($new_errors = self::validate_field($uri, $value))
            {
                $GLOBALS['ContentArchiveValidatorErrors'] = array_merge($GLOBALS['ContentArchiveValidatorErrors'], $new_errors);
            }
        }
    }
    
    public static function validate_field($uri, $value)
    {
        $errors = array();
        $rules = self::rules();
        if(@!$rules[$uri]) return $errors;
        
        $methods = $rules[$uri];
        if(is_array($methods))
        {
            foreach($methods as $method)
            {
                if($new_error = self::apply_rule($method, $value)) $errors[] = array($new_error, $value);
            }
        }else
        {
            if($new_error = self::apply_rule($methods, $value)) $errors[] = array($new_error, $value);
        }
        return $errors;
    }
    
    private static function apply_rule($method, $value)
    {
        if(method_exists(__CLASS__, $method))
        {
            return call_user_func(array(__CLASS__, $method), $value);
        }else trigger_error("Undefined method `$method` on ContentArchiveValidator", E_USER_WARNING);
    }
    
    private static function rules()
    {
        $rules = array(
            'http://www.eol.org/schema/transfer#type'       => 'check_data_type',
            'http://purl.org/dc/terms/type'                 => 'check_data_type',
            'http://www.eol.org/schema/transfer#license'    => 'check_license',
            'http://purl.org/dc/terms/license'              => 'check_license',
            'http://www.eol.org/schema/transfer#subject'    => 'check_subject',
            'http://purl.org/dc/terms/subject'              => 'check_subject',
            'http://www.eol.org/schema/transfer#language'   => 'check_language',
            'http://purl.org/dc/terms/language'             => 'check_language');
        return $rules;
    }
    
    public static function check_data_type($value)
    {
        if(!in_array($value, array(
            'http://purl.org/dc/dcmitype/StillImage',
            'http://purl.org/dc/dcmitype/Sound',
            'http://purl.org/dc/dcmitype/Text',
            'http://purl.org/dc/dcmitype/MovingImage'))) return 'Data type must be a valid DublinCore type';
    }
    
    public static function check_subject($value)
    {
        if(!$value) return;
        if(!in_array($value, array(
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Behaviour',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Conservation',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Cyclicity',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Cytology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Diseases',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Dispersal',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Ecology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Evolution',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Growth',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Key',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Legislation',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#LifeCycle',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#LifeExpectancy',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#LookAlikes',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Management',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Migration',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#MolecularBiology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Physiology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#PopulationBiology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Procedures',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Reproduction',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Threats',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Trends',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TrophicStrategy',
            'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses'))) return 'Subject isnt recommended by EOL';
    }
    
    public static function check_language($value)
    {
        if(!preg_match("/^[a-z]{2,3}$/i", $value)) return 'Langauge must be a valid ISO code';
    }
    
    public static function check_license($value)
    {
        if(!preg_match("/^http:\/\/creativecommons.org\/licenses\/(by|by-nc|by-sa|by-nc-sa|publicdomain)\/(1\.0|2\.0|2\.5|3\.0)\/$/i", $value) &&
           $value != 'not applicable') return 'License must be a valid creative commons license';
    }
    
}

?>