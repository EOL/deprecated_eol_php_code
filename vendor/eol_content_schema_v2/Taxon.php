<?php
namespace eol_schema;

class Taxon extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = 'http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd';
    const ROW_TYPE = 'http://rs.tdwg.org/dwc/terms/Taxon';
    
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
            
            // add dc:source
            $property = array();
            $property['name'] = 'source';
            $property['namespace'] = 'http://purl.org/dc/terms';
            $property['uri'] = "http://purl.org/dc/terms/source";
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