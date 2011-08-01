<?php
namespace php_active_record;

class ArchiveDataIngester
{
    private $resource;
    
    public function __construct($resource)
    {
        $this->resource = $resource;
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    public function parse($validate = true)
    {
        echo "A". $this->resource->archive_path() ."\n";
        if(!is_dir($this->resource->archive_path())) return false;
        echo "B\n";
        // set valid to true if we don't need validation
        $valid = $validate ? ContentArchiveValidator::validate(null, $this->resource->archive_path()) : true;
        if($valid !== true) return false;
        echo "C\n";
        
        $this->hierarchy_entry_ids = array();
        $this->content_manager = new ContentManager();
        $archive = new ContentArchiveReader(null, $this->resource->archive_path());
        // $archive->process_table("http://rs.tdwg.org/dwc/terms/Taxon", array($this, 'insert_taxon'));
        $archive->process_table("http://labs2.eol.org/schema/ontology.rdf#MediaResource", array($this, 'insert_data_object'));
    }
    
    public function insert_taxon($row)
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        $taxon_parameters["source_url"] = @self::field_decode($row['http://purl.org/dc/terms/source']);
        $taxon_parameters["kingdom"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/kingdom']);
        $taxon_parameters["phylum"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/phylum']);
        $taxon_parameters["class"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/class']);
        $taxon_parameters["order"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/order']);
        $taxon_parameters["family"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/family']);
        $taxon_parameters["genus"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/genus']);
        $taxon_parameters["scientific_name"] = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/scientificName']);
        $taxon_parameters["taxon_modified_at"] = @self::field_decode($row['http://purl.org/dc/terms/modified']);
        print_r($taxon_parameters);
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
        
        $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($taxon_parameters, $this->resource->hierarchy_id);
        if(@!$hierarchy_entry->id) return false;
        $this->hierarchy_entry_ids[$taxon_parameters["identifier"]] = $hierarchy_entry->id;
        // $this->resource->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
    }
    
    public function insert_data_object($row)
    {
        $data_object = new DataObject();
        $data_object->identifier = @self::field_decode($row['http://www.eol.org/schema/transfer#mediaResourceID']);
        $data_object->data_type = DataType::find_or_create_by_schema_value(@self::field_decode($row['http://www.eol.org/schema/transfer#type']));
        $data_object->mime_type = MimeType::find_or_create_by_translated_label(@self::field_decode($row['http://www.eol.org/schema/transfer#subject']));
        $data_object->object_created_at = @self::field_decode($row['http://www.eol.org/schema/transfer#created']);
        $data_object->object_modified_at = @self::field_decode($row['http://www.eol.org/schema/transfer#modified']);
        $data_object->object_title = @self::field_decode($row['http://www.eol.org/schema/transfer#title']);
        // $data_object->language = Language::find_or_create_for_parser(Functions::import_decode($d_dc->language));
        $data_object->license = License::find_or_create_for_parser(@self::field_decode($row['http://www.eol.org/schema/transfer#license']));
        // $data_object->rights_statement = Functions::import_decode($d_dc->rights, 0, 0);
        // $data_object->rights_holder = Functions::import_decode($d_dcterms->rightsHolder, 0, 0);
        // $data_object->bibliographic_citation = Functions::import_decode($d_dcterms->bibliographicCitation, 0, 0);
        // $data_object->source_url = Functions::import_decode($d_dc->source);
        $data_object->description = @self::field_decode($row['http://www.eol.org/schema/transfer#description']);
        $data_object->object_url = @self::field_decode($row['http://www.eol.org/schema/transfer#fileURL']);
        // $data_object->thumbnail_url = Functions::import_decode($d->thumbnailURL);
        // $data_object->location = Functions::import_decode($d->location, 0, 0);
        // 
        // $data_object_parameters = array();
        // if(!$data_object->language)
        // {
        //     $xml_attr = $d_dc->description->attributes("http://www.w3.org/XML/1998/namespace");
        //     $data_object->language = Language::find_or_create_for_parser(@Functions::import_decode($xml_attr["lang"]));
        // }
        // 
        // //TODO - update this
        // if($data_object->mime_type && $data_object->mime_type->equals(MimeType::flash()) && $data_object->is_video())
        // {
        //     $data_object->data_type = DataType::youtube();
        // }
        // 
        // //take the taxon's source_url if none present
        // if(!@$data_object->source_url && @$taxon_parameters["source_url"]) $data_object->source_url = $taxon_parameters["source_url"];
        // 
        // // Turn newlines into paragraphs
        // $data_object->description = str_replace("\n","</p><p>", $data_object->description);
        // 
        // 
        // /* Checking requirements*/
        // 
        // //if text: must have description
        // if($data_object->data_type->equals(DataType::text()) && !$data_object->description) return;
        // 
        // //if image, movie or sound: must have object_url
        // if(($data_object->data_type->equals(DataType::video()) || $data_object->data_type->equals(DataType::sound()) || $data_object->data_type->equals(DataType::image())) && !$data_object->object_url) return;
        
        print_r($data_object);
        
        
        list($data_object, $status) = DataObject::find_and_compare($this->resource, $data_object, $this->content_manager);
        if(@!$data_object->id) return false;
        
        // $data_object->delete_hierarchy_entries();
        // $hierarchy_entry->add_data_object($data_object->id, $d);
        // $this->resource->harvest_event->add_data_object($data_object, $status);
        // 
        // $this->mysqli->insert("INSERT IGNORE INTO data_objects_hierarchy_entries (hierarchy_entry_id, data_object_id) VALUES ($this->id, $data_object_id)");
        // $this->mysqli->insert("INSERT IGNORE INTO data_objects_taxon_concepts (taxon_concept_id, data_object_id) VALUES ($this->taxon_concept_id, $data_object_id)");
        // 
        
    }
    
    private static function field_decode($string)
    {
        $string = str_replace("\\n", "\n", $string);
        $string = str_replace("\\r", "\r", $string);
        $string = str_replace("\\t", "\t", $string);
        return $string;
    }
}

?>