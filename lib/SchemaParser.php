<?php
namespace php_active_record;

class SchemaParser
{
    public static function parse($uri, &$connection, $validate = true)
    {
        if(!$uri) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        // set valid to true if we don't need validation
        $valid = $validate ? SchemaValidator::validate($uri) : true;
        if($valid !== true) return false;
        
        $reader = new \XMLReader();
        $reader->open($uri);
        $resource = $connection->get_resource();
        
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
            {
                $taxon_xml = $reader->readOuterXML();
                $t = simplexml_load_string($taxon_xml, null, LIBXML_NOCDATA);
                
                $taxon_parameters = self::read_taxon_xml($t, $resource);
                $connection->add_taxon($taxon_parameters);
                
                $i++;
                if($i%100==0)
                {
                	debug("Parsed taxon $i");
                }
                
                // trying now to see if commiting every 20 taxa will help with replication
                // if($i%20==0) 
                $mysqli->commit();
                
                if(defined("DEBUG_PARSE_TAXON_LIMIT") && DEBUG_PARSE_TAXON_LIMIT && $i >= DEBUG_PARSE_TAXON_LIMIT) break;
            }
        }
    }
    
    public static function read_taxon_xml($t, $resource)
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
        $taxon_parameters["rank"] = Rank::find_or_create_by_translated_label(Functions::import_decode($t->rank));
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
        
        $taxon_parameters["refs"] = array();
        foreach($t->reference as $r)
        {
            $reference = Functions::import_decode((string) $r, 0, 0);
            if(!$reference) continue;
            
            $ref = Reference::find_or_create_by_full_reference($reference);
            $taxon_parameters["refs"][] = $ref;
            
            $id_labels = array("bici", "coden", "doi", "eissn", "handle", "issn", "isbn", "lsid", "oclc", "sici", "url", "urn");
            $attr = $r->attributes();
            foreach($id_labels as $label)
            {
                if($id = @Functions::import_decode($attr[$label], 0, 0))
                {
                    $type = RefIdentifierType::find_or_create_by_label($label);
                    $ref->add_ref_identifier(@$type->id ?: 0, $id);
                }
            }
        }
        
        $taxon_parameters["data_objects"] = array();
        foreach($t->dataObject as $d)
        {
            $d_dc = $d->children("http://purl.org/dc/elements/1.1/");
            $d_dcterms = $d->children("http://purl.org/dc/terms/");
            $d_geo = $d->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
            
            $data_object = new DataObject();
            $data_object->identifier = Functions::import_decode($d_dc->identifier);
            $data_object->data_type = DataType::find_or_create_by_schema_value(Functions::import_decode($d->dataType));
            $data_object->mime_type = MimeType::find_or_create_by_translated_label(Functions::import_decode($d->mimeType));
            $data_object->object_created_at = Functions::import_decode($d_dcterms->created);
            $data_object->object_modified_at = Functions::import_decode($d_dcterms->modified);
            $data_object->object_title = Functions::import_decode($d_dc->title, 0, 0);
            $data_object->language = Language::find_or_create_for_parser(Functions::import_decode($d_dc->language));
            $data_object->license = License::find_or_create_for_parser(Functions::import_decode($d->license));
            $data_object->rights_statement = Functions::import_decode($d_dc->rights, 0, 0);
            $data_object->rights_holder = Functions::import_decode($d_dcterms->rightsHolder, 0, 0);
            $data_object->bibliographic_citation = Functions::import_decode($d_dcterms->bibliographicCitation, 0, 0);
            $data_object->source_url = Functions::import_decode($d_dc->source);
            $data_object->description = Functions::import_decode($d_dc->description, 0, 0);
            $data_object->object_url = Functions::import_decode($d->mediaURL);
            $data_object->thumbnail_url = Functions::import_decode($d->thumbnailURL);
            $data_object->location = Functions::import_decode($d->location, 0, 0);
            if(@$d->additionalInformation) $data_object->additional_information = (array) $d->additionalInformation;
            if($r = (string) @$d->additionalInformation->rating)
            {
                if((is_numeric($r)) && $r > 0 && $r <= 5)
                {
                    $data_object->data_rating = $r;
                }
            }
            
            if($subtype = @$d->additionalInformation->subtype)
            {
                if($dt = DataType::find_or_create_by_schema_value(Functions::import_decode($subtype)))
                {
                    $data_object->data_subtype_id = $dt->id;
                }
            }
            
            $data_object_parameters = array();
            if(!$data_object->language)
            {
                $xml_attr = $d_dc->description->attributes("http://www.w3.org/XML/1998/namespace");
                $data_object->language = Language::find_or_create_for_parser(@Functions::import_decode($xml_attr["lang"]));
            }
            if(!$data_object->language && $resource->language)
            {
                $data_object->language = $resource->language;
            }
            
            //TODO - update this
            if($data_object->mime_type && $data_object->mime_type->equals(MimeType::flash()) && $data_object->is_video())
            {
                $data_object->data_type = DataType::youtube();
                $data_object->data_type_id = DataType::youtube()->id;
            }
            
            //take the taxon's source_url if none present
            if(!@$data_object->source_url && @$taxon_parameters["source_url"]) $data_object->source_url = $taxon_parameters["source_url"];
            
            // Turn newlines into paragraphs
            $data_object->description = str_replace("\n","</p><p>", $data_object->description);
            
            
            /* Checking requirements*/
            
            //if text: must have description
            if($data_object->data_type->equals(DataType::text()) && !$data_object->description) continue;
            
            //if image, movie or sound: must have object_url
            if(($data_object->data_type->equals(DataType::video()) || $data_object->data_type->equals(DataType::sound()) || $data_object->data_type->equals(DataType::image())) && !$data_object->object_url) continue;
            
            
            
            
            
            
            $data_object->latitude = 0;
            $data_object->longitude = 0;
            $data_object->altitude = 0;
            foreach($d_geo->Point as $p)
            {
                $p_geo = $p->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
                $data_object->latitude = Functions::import_decode($p_geo->lat);
                $data_object->longitude = Functions::import_decode($p_geo->long);
                $data_object->altitude = Functions::import_decode($p_geo->alt);
            }
            
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
            
            $data_object_parameters["audiences"] = array();
            foreach($d->audience as $a)
            {
                $data_object_parameters["audiences"][] = Audience::find_or_create_by_translated_label(trim((string) $a));
            }
            
            $data_object_parameters["info_items"] = array();
            foreach($d->subject as $s)
            {
                $data_object_parameters["info_items"][] = InfoItem::find_or_create_by_schema_value(trim((string) $s));
            }
            
            if($subject = @$d->additionalInformation->subject)
            {
                if($ii = InfoItem::find_or_create_by_schema_value(trim((string) $subject)))
                {
                    $data_object_parameters["info_items"] = array($ii);
                }
            }
            
            
            // EXCEPTIONS
            if($data_object->is_text())
            {
                if($resource->title == "BOLD Systems Resource")
                {
                    // EXCEPTION - overriding the subject for BOLD
                    $data_object_parameters["info_items"] = array(InfoItem::find_or_create_by_schema_value('http://www.eol.org/voc/table_of_contents#Barcode'));
                }elseif($resource->title == "Wikipedia")
                {
                    // EXCEPTION - overriding the subject for Wikipedia
                    $data_object_parameters["info_items"] = array(InfoItem::find_or_create_by_schema_value('http://www.eol.org/voc/table_of_contents#Wikipedia'));
                }elseif($resource->title == "IUCN Red List")
                {
                    if($data_object->object_title == "IUCNConservationStatus")
                    {
                        // EXCEPTION - overriding the data type for IUCN text
                        $data_object->data_type_id = DataType::iucn()->id;
                        $data_object->data_type = DataType::iucn();
                    }
                }
            }
            
            
            
            
            $data_object_parameters["refs"] = array();
            foreach($d->reference as $r)
            {
                $reference = Functions::import_decode((string) $r, 0, 0);
                if(!$reference) continue;
                
                $ref = Reference::find_or_create_by_full_reference($reference);
                $data_object_parameters["refs"][] = $ref;
                
                $id_labels = array("bici", "coden", "doi", "eissn", "handle", "issn", "isbn", "lsid", "oclc", "sici", "url", "urn");
                $attr = $r->attributes();
                foreach($id_labels as $label)
                {
                    if($id = @Functions::import_decode($attr[$label], 0, 0))
                    {
                        $type = RefIdentifierType::find_or_create_by_label($label);
                        $ref->add_ref_identifier(@$type->id ?: 0, $id);
                    }
                }
            }
            
            $taxon_parameters["data_objects"][] = array($data_object, $data_object_parameters);
            unset($data_object);
        }
        
        return $taxon_parameters;
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
                    $data_type = Functions::import_decode($d->dataType);
                    $license = Functions::import_decode($d->license);
                    $source_url = Functions::import_decode($d_dc->source);
                    $description = Functions::import_decode($d_dc->description, 0, 0);
                    $object_url = Functions::import_decode($d->mediaURL);
                    
                    
                    $subjects = array();
                    foreach($d->subject as $s)
                    {
                        $subjects[] = trim((string) $s);
                    }
                    
                    
                    /* Checking requirements*/
                    if(!$identifier) $warnings[$scientific_name]["data object without dc:identifier"] = true;
                    if(!$license) $warnings[$scientific_name]["data object without license"] = true;
                    
                    //if text: must have description
                    if($data_type == "http://purl.org/dc/dcmitype/Text" && !$description) $errors[$scientific_name]["text without dc:description"] = true;
                    
                    //if text: must have subject
                    if($data_type == "http://purl.org/dc/dcmitype/Text" && !$subjects) $errors[$scientific_name]["text without subject"] = true;
                    
                    //if image, movie or sound: must have object_url
                    if($data_type != "http://purl.org/dc/dcmitype/Text" && !$object_url) $errors[$scientific_name]["media without mediaURL"] = true;
                }
                
                //unset($xml->taxon[$i]);
                $xml->taxon[$i] = null;
                $i++;
                
                //if($i%100==0 && DEBUG) debug("Parsed taxon $i");
                //if(defined("DEBUG_PARSE_TAXON_LIMIT") && $i >= DEBUG_PARSE_TAXON_LIMIT) break;
            }
        }
        
        return array($errors, $warnings);
    }
}

?>
