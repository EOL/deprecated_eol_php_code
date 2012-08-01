<?php
namespace eol_schema;

class Taxon extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = 'http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd';
    const ROW_TYPE = 'http://rs.tdwg.org/dwc/terms/Taxon';
    
    public static function validation_rules()
    {
        static $rules = array();
        if(!$rules)
        {
            // these rules apply to individual fields
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/scientificName',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::exists',
                'failure_type'          => 'warning',
                'failure_message'       => 'Taxa should have scientificNames'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/taxonRank',
                'validation_function'   => 'eol_schema\Taxon::valid_rank',
                'failure_type'          => 'warning',
                'failure_message'       => 'Unrecognized taxon rank'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/taxonomicStatus',
                'validation_function'   => 'eol_schema\Taxon::valid_taxon_status',
                'failure_type'          => 'warning',
                'failure_message'       => 'Unrecognized taxonomicStatus'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/scientificName',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/kingdom',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/phylum',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/class',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/order',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/family',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            $rules[] = new ContentArchiveFieldValidationRule(array(
                'field_uri'             => 'http://rs.tdwg.org/dwc/terms/genus',
                'validation_function'   => 'php_active_record\ContentArchiveValidator::is_utf8',
                'failure_type'          => 'warning',
                'failure_message'       => 'Names should be encoded in UTF-8'));
            
            // these rules apply to entire rows
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\Taxon::valid_identifier',
                'failure_type'          => 'error',
                'failure_message'       => 'Taxa must have identifiers'));
            
            $rules[] = new ContentArchiveRowValidationRule(array(
                'validation_function'   => 'eol_schema\Taxon::validate_presence_of_any_name',
                'failure_type'          => 'warning',
                'failure_message'       => 'Taxa should contain a scientificName or minimally a kingdom, phylum, class, order, family or genus'));
        }
        return $rules;
    }
    
    public static function valid_taxon_status($v)
    {
        if($v && !in_array($v, array(
            'accepted',
            'valid',
            'current',
            'invalid',
            'synonym',
            'homotypic synonym',
            'heterotypic synonym',
            'misapplied name')))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_rank($v)
    {
        if($v && !in_array(strtolower($v), array(
            'kingdom',
            'regnum',
            'subkingdom',
            'subregnum',
            'division',
            'phylum',
            'divisio',
            'subdivision',
            'subphylum',
            'subdivisio',
            'class',
            'classis',
            'subclass',
            'subclassis',
            'order',
            'ordo',
            'suborder',
            'subordo',
            'superfamily',
            'family',
            'familia',
            'subfamily',
            'subfamilia',
            'tribe',
            'tribus',
            'subtribe',
            'subtribus',
            'genus',
            'subgenus',
            'section',
            'sectio',
            'subsection',
            'subsectio',
            'series',
            'subseries',
            'species',
            'subspecies',
            'variety',
            'varietas',
            'subvariety',
            'subvarietas',
            'form',
            'forma',
            'subform',
            'subforma')))
        {
            return false;
        }
        return true;
    }
    
    public static function valid_identifier($fields)
    {
        if(@!$fields['http://rs.tdwg.org/dwc/terms/taxonID'] &&
           @!$fields['http://purl.org/dc/terms/identifier'])
        {
            return false;
        }
        return true;
    }
    
    public static function validate_presence_of_any_name($fields)
    {
        if(@!$fields['http://rs.tdwg.org/dwc/terms/scientificName'] &&
           @!$fields['http://rs.tdwg.org/dwc/terms/kingdom'] &&
           @!$fields['http://rs.tdwg.org/dwc/terms/phylum'] &&
           @!$fields['http://rs.tdwg.org/dwc/terms/class'] &&
           @!$fields['http://rs.tdwg.org/dwc/terms/order'] &&
           @!$fields['http://rs.tdwg.org/dwc/terms/family'] &&
           @!$fields['http://rs.tdwg.org/dwc/terms/genus'])
        {
            return false;
        }
        return true;
    }
    
    
    
    
    
    
    /*
        Taxon is special in that it doesn't have an extension and allows fields from various schemas.
        This method helps it load the URIs of the properties Taxon should contain.
    */
    protected function load_extension()
    {
        if(isset($GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties']))
        {
            $this->accepted_properties = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'];
            $this->accepted_properties_by_name = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'];
            $this->accepted_properties_by_uri = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'];
            
        }else
        {
            $this->accepted_properties = array();
            $this->accepted_properties_by_name = array();
            $this->accepted_properties_by_uri = array();
            
            // add dwc:taxonID
            $property = array();
            $property['name'] = 'taxonID';
            $property['namespace'] = 'http://rs.tdwg.org/dwc/terms';
            $property['uri'] = "http://rs.tdwg.org/dwc/terms/taxonID";
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
            
            // add dc:identifier
            $property = array();
            $property['name'] = 'identifier';
            $property['namespace'] = 'http://purl.org/dc/terms';
            $property['uri'] = "http://purl.org/dc/terms/identifier";
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
            
            // add dc:source
            $property = array();
            $property['name'] = 'source';
            $property['namespace'] = 'http://purl.org/dc/terms';
            $property['uri'] = "http://purl.org/dc/terms/source";
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
            
            // add ac:furthurInformationURL
            $property = array();
            $property['name'] = 'furtherInformationURL';
            $property['namespace'] = 'http://rs.tdwg.org/ac/terms';
            $property['uri'] = "http://rs.tdwg.org/ac/terms/furtherInformationURL";
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
            
            // add eol:referenceID
            $property = array();
            $property['name'] = 'referenceID';
            $property['namespace'] = 'http://eol.org/schema/media';
            $property['uri'] = "http://eol.org/schema/reference/referenceID";
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
            
            
            $schema_xml = self::download_extension(static::EXTENSION_URL);
            $xml = simplexml_load_string($schema_xml);
            $xml_schema = $xml->children("http://www.w3.org/2001/XMLSchema");
            foreach($xml_schema->xpath("//xs:group[@name='TaxonTerms']//xs:element") as $e)
            {
                $attr = $e->attributes();
                $property = array();
                $name = (string) $attr['ref'];
                $name = str_replace("dwc:", "", $name);
                $property['name'] = $name;
                $property['namespace'] = 'http://rs.tdwg.org/dwc/terms';
                $property['uri'] = "http://rs.tdwg.org/dwc/terms/" . $property['name'];
                
                $this->accepted_properties[] = $property;
                $this->accepted_properties_by_name[$property['name']] = $property;
                $this->accepted_properties_by_uri[$property['uri']] = $property;
            }
            
            foreach($xml_schema->xpath("//xs:group[@name='RecordLevelTerms']//xs:element") as $e)
            {
                $attr = $e->attributes();
                $property = array();
                $name = (string) $attr['ref'];
                if(preg_match("/^dcterms:/", $name))
                {
                    $name = str_replace("dcterms:", "", $name);
                    $namespace = "http://purl.org/dc/terms";
                }elseif(preg_match("/^dwc:/", $name))
                {
                    $name = str_replace("dwc:", "", $name);
                    $namespace = "http://rs.tdwg.org/dwc/terms";
                }else continue;
                
                $property['name'] = $name;
                $property['namespace'] = $namespace;
                $property['uri'] = $namespace . "/" . $property['name'];
                
                $this->accepted_properties[] = $property;
                $this->accepted_properties_by_name[$property['name']] = $property;
                $this->accepted_properties_by_uri[$property['uri']] = $property;
            }
            
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'] = $this->accepted_properties;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'] = $this->accepted_properties_by_name;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'] = $this->accepted_properties_by_uri;
        }
    }
}

?>