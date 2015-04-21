<?php
namespace php_active_record;

class TranslationSchemaParser
{
    public static function parse($uri, $callback, $validate = true, $resource = null)
    {
        if(!$uri) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        // set valid to true if we don't need validation
        $valid = $validate ? SchemaValidator::validate($uri) : true;
        if($valid !== true) return false;
        
        $reader = new \XMLReader();
        $reader->open($uri);
        
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
            {
                $taxon_xml = $reader->readOuterXML();
                $t = simplexml_load_string($taxon_xml, null, LIBXML_NOCDATA);
                
                $taxon_parameters = self::read_taxon_xml($t);
                call_user_func($callback, $taxon_parameters, $resource);
                
                $i++;
                if($i%100==0){
                	debug("Parsed taxon $i");
                 }
                
                // trying now to see if commiting every 200 taxa will help with replication
                if($i%200==0) $mysqli->commit();
                
                if(defined("DEBUG_PARSE_TAXON_LIMIT") && DEBUG_PARSE_TAXON_LIMIT && $i >= DEBUG_PARSE_TAXON_LIMIT) break;
            }
        }
    }
    
    public static function read_taxon_xml($t)
    {
        $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
        $t_dcterms = $t->children("http://purl.org/dc/terms/");
        $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = Functions::import_decode($t_dc->identifier);
        $taxon_parameters["source_url"] = Functions::import_decode($t_dc->source);
        $taxon_parameters["kingdom"] = Functions::import_decode($t_dwc->Kingdom);
        $taxon_parameters["phylum"] = Functions::import_decode($t_dwc->Phylum);
        $taxon_parameters["class"] = Functions::import_decode($t_dwc->Class);
        $taxon_parameters["order"] = Functions::import_decode($t_dwc->Order);
        $taxon_parameters["family"] = Functions::import_decode($t_dwc->Family);
        $taxon_parameters["genus"] = Functions::import_decode($t_dwc->Genus);
        $taxon_parameters["scientific_name"] = Functions::import_decode($t_dwc->ScientificName);
        $taxon_parameters["taxon_created_at"] = trim($t_dcterms->created);
        $taxon_parameters["taxon_modified_at"] = trim($t_dcterms->modified);
        
        if($taxon_parameters["scientific_name"])
        {
            $taxon_parameters["name"] = Name::find_or_create_by_string($taxon_parameters["scientific_name"]);
        }else
        {
            if($name = $taxon_parameters["genus"])
            {
                $taxon_parameters["scientific_name"] = $name;
                $taxon_parameters["name"] = Name::find_or_create_by_string($name);
                $taxon_parameters["genus"] = "";
            }elseif($name = $taxon_parameters["family"])
            {
                $taxon_parameters["scientific_name"] = $name;
                $taxon_parameters["name"] = Name::find_or_create_by_string($name);
                $taxon_parameters["family"] = "";
            }elseif($name = $taxon_parameters["order"])
            {
                $taxon_parameters["scientific_name"] = $name;
                $taxon_parameters["name"] = Name::find_or_create_by_string($name);
                $taxon_parameters["order"] = "";
            }elseif($name = $taxon_parameters["class"])
            {
                $taxon_parameters["scientific_name"] = $name;
                $taxon_parameters["name"] = Name::find_or_create_by_string($name);
                $taxon_parameters["class"] = "";
            }elseif($name = $taxon_parameters["phylum"])
            {
                $taxon_parameters["scientific_name"] = $name;
                $taxon_parameters["name"] = Name::find_or_create_by_string($name);
                $taxon_parameters["phylum"] = "";
            }elseif($name = $taxon_parameters["kingdom"])
            {
                $taxon_parameters["scientific_name"] = $name;
                $taxon_parameters["name"] = Name::find_or_create_by_string($name);
                $taxon_parameters["kingdom"] = "";
            }else return;
        }
        
        $taxon_parameters["common_names"] = array();
        foreach($t->commonName as $c)
        {
            $common_name = Functions::import_decode((string) $c);
            if(!$common_name) continue;
            $xml_attr = $c->attributes("http://www.w3.org/XML/1998/namespace");
            $params = array("name" => $common_name,
                            "language" => Language::find_or_create_for_parser(@Functions::import_decode($xml_attr["lang"])));
            $taxon_parameters["common_names"][] = $params;
        }
        
        $taxon_parameters["synonyms"] = array();
        foreach($t->synonym as $s)
        {
            $synonym = Functions::import_decode((string) $s);
            if(!$synonym) continue;
            
            $attr = $s->attributes();
            if(!@$attr["relationship"]) $attr["relationship"] = 'synonym';
            $params = array("name" => Name::find_or_create_by_string($synonym),
                            "synonym_relation" => SynonymRelation::find_or_create_by_translated_label(trim($attr["relationship"])));
            $taxon_parameters["synonyms"][] = $params;
        }
        
        $taxon_parameters["agents"] = array();
        foreach($t->agent as $a)
        {
            $agent_name = Functions::import_decode((string) $a);
            if(!$agent_name) continue;
            
            $attr = $a->attributes();
            $params = array("full_name" => Functions::import_decode((string) $a, 0, 0),
                            "homepage" => @Functions::import_decode($attr["homepage"]),
                            "logo_url" => @Functions::import_decode($attr["logoURL"]),
                            "agent_role" => AgentRole::find_or_create_by_translated_label(@trim($attr["role"])));
            $taxon_parameters["agents"][] = $params;
            unset($params);
        }
        
        $taxon_parameters["data_objects"] = array();
        foreach($t->dataObject as $d)
        {
            $d_dc = $d->children("http://purl.org/dc/elements/1.1/");
            $d_dcterms = $d->children("http://purl.org/dc/terms/");
            $d_geo = $d->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
            
            $data_object = new DataObject();
            $data_object->identifier = Functions::import_decode($d_dc->identifier);
            $data_object->object_created_at = Functions::import_decode($d_dcterms->created);
            $data_object->object_modified_at = Functions::import_decode($d_dcterms->modified);
            $data_object->object_title = Functions::import_decode($d_dc->title, 0, 0);
            $data_object->language = Language::find_or_create_for_parser(Functions::import_decode($d_dc->language));
            $data_object->rights_statement = Functions::import_decode($d_dc->rights, 0, 0);
            $data_object->rights_holder = Functions::import_decode($d_dcterms->rightsHolder, 0, 0);
            $data_object->description = Functions::import_decode($d_dc->description, 0, 0);
            $data_object->location = Functions::import_decode($d->location, 0, 0);
            
            $data_object_parameters = array();
            if(!$data_object->language)
            {
                $xml_attr = $d_dc->description->attributes("http://www.w3.org/XML/1998/namespace");
                $data_object->language = Language::find_or_create_for_parser(@Functions::import_decode($xml_attr["lang"]));
            }
            
            //take the taxon's source_url if none present
            if(!@$data_object->source_url && @$taxon_parameters["source_url"]) $data_object->source_url = $taxon_parameters["source_url"];
            
            $data_object_parameters["agents"] = array();
            foreach($d->agent as $a)
            {
                $agent_name = Functions::import_decode((string) $a);
                if(!$agent_name) continue;

                $attr = $a->attributes();
                $params = array("full_name" => Functions::import_decode((string) $a, 0, 0),
                                "homepage" => @Functions::import_decode($attr["homepage"]),
                                "logo_url" => @Functions::import_decode($attr["logoURL"]),
                                "agent_role" => AgentRole::find_or_create_by_translated_label(@trim($attr["role"])));
                $data_object_parameters["agents"][] = $params;
                unset($params);
            }
            
            if($translation_information = @$d->additionalInformation->translation)
            {
                $data_object->EOLDataObjectID = (string) $translation_information->EOLDataObjectID;
                if($translator = (string) $translation_information->translator)
                {
                    $data_object_parameters["agents"][] = self::translation_agent($translator, 'Translator');
                }
                if($scientificReviewer = (string) $translation_information->scientificReviewer)
                {
                    $data_object_parameters["agents"][] = self::translation_agent($scientificReviewer, 'Scientific Reviewer');
                }
                if($linguisticReviewer = (string) $translation_information->linguisticReviewer)
                {
                    $data_object_parameters["agents"][] = self::translation_agent($linguisticReviewer, 'Linguistic Reviewer');
                }
            }
            
            $taxon_parameters["data_objects"][] = array($data_object, $data_object_parameters);
            unset($data_object);
        }
        
        return $taxon_parameters;
    }
    
    public static function translation_agent($name, $role)
    {
        $params = array("full_name" => Functions::import_decode($name, 0, 0),
                        "agent_role" => AgentRole::find_or_create_by_translated_label($role));
        return $params;
    }
    
    public static function eol_schema_validate($uri)
    {
        if(!$uri) return false;
        
        $valid = SchemaValidator::validate($uri);
        if($valid !== true) return array();
        
        $errors = array();
        $warnings = array();
        
        $reader = new \XMLReader();
        $reader->open($uri);
        
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
            {
                $taxon_xml = $reader->readOuterXML();
                $t = simplexml_load_string($taxon_xml, null, LIBXML_NOCDATA);
                
                $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                
                $identifier = Functions::import_decode($t_dc->identifier);
                $source_url = Functions::import_decode($t_dc->source);
                $scientific_name = Functions::import_decode($t_dwc->ScientificName);
                
                if(!$identifier) $warnings[$scientific_name]["taxon without dc:identifier"] = true;
                if(!$source_url) $warnings[$scientific_name]["taxon without dc:source"] = true;
                
                foreach($t->dataObject as $d)
                {
                    $d_dc = $d->children("http://purl.org/dc/elements/1.1/");
                    
                    $identifier = Functions::import_decode($d_dc->identifier);
                    
                    /* Checking requirements*/
                    if(!$identifier) $warnings[$scientific_name]["data object without dc:identifier"] = true;
                }
                
                $xml->taxon[$i] = null;
                $i++;
            }
        }
        
        return array($errors, $warnings);
    }
}

?>