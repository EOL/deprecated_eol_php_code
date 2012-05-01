<?php
namespace php_active_record;

class ArchiveDataIngester
{
    private $resource;
    
    public function __construct($harvest_event)
    {
        $this->harvest_event = $harvest_event;
        $this->mysqli =& $GLOBALS['db_connection'];
        $this->valid_taxonomic_statuses = array("valid", "accepted");
    }
    
    public function parse($validate = true)
    {
        if(!is_dir($this->harvest_event->resource->archive_path())) return false;
        $this->archive_reader = new ContentArchiveReader(null, $this->harvest_event->resource->archive_path());
        $this->archive_validator = new ContentArchiveValidator($this->archive_reader);
        $this->content_manager = new ContentManager();
        
        // set valid to true if we don't need validation
        $valid = $validate ? $this->archive_validator->is_valid() : true;
        if($valid !== true) return $this->archive_validator->errors();
        
        $this->taxon_reference_ids = array();
        $this->media_reference_ids = array();
        $this->media_agent_ids = array();
        
        $this->mysqli->begin_transaction();
        $this->start_reading_taxa();
        $this->mysqli->commit();
        $this->archive_reader->process_table("http://eol.org/schema/reference/Reference", array($this, 'insert_references'));
        // $archive_reader->process_table("http://eol.org/schema/media/Document", array($this, 'insert_data_object'));
        $this->mysqli->end_transaction();
    }
    
    public function start_reading_taxa()
    {
        $this->children = array();
        $this->synonyms = array();
        $this->archive_reader->process_table("http://rs.tdwg.org/dwc/terms/Taxon", array($this, 'read_taxon'));
        $this->begin_adding_taxa();
    }
    
    private function begin_adding_taxa()
    {
        $this->taxon_ids_inserted = array();
        if(isset($this->children[0]))
        {
            // get all the roots, or taxa with no parents
            foreach($this->children[0] as $taxon_id => &$row)
            {
                $parent_hierarchy_entry_id = 0;
                $ancestry = "";
                $this->add_hierarchy_entry($row, $parent_hierarchy_entry_id, $ancestry);
                unset($this->children[$taxon_id]);
            }
        }else echo "THERE ARE NO ROOT TAXA\nAborting import\n";
    }
    
    public function read_taxon($row)
    {
        static $i = 0;
        if($i % 500 == 0 && $GLOBALS['ENV_DEBUG'])
        {
            echo "Loading taxon $i: ".memory_get_usage()."\n";
        }
        $i++;
        
        // assume the taxon is valid by default
        $is_valid = true;
        // if the taxon has a status which isn't valid, then it isn't valid
        $taxonomic_status = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonomicStatus']);
        if($taxonomic_status && trim($taxonomic_status) != '')
        {
            $is_valid = Functions::array_searchi($taxonomic_status, $this->valid_taxonomic_statuses);
            // $is_valid might be zero at this point so we need to check
            if($is_valid === null) $is_valid = false;
        }
        
        // if the taxon has an acceptedNameUsageID then it isn't valid
        $accepted_taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']);
        if($is_valid && $accepted_taxon_id && $accepted_taxon_id != '')
        {
            $is_valid = false;
        }
        
        $taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        if(!$taxon_id) $taxon_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        $parent_taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/parentNameUsageID']);
        
        if($taxon_id && $is_valid)
        {
            if(!$parent_taxon_id) $parent_taxon_id = 0;
            $this->children[$parent_taxon_id][] = $row;
        }elseif(!$is_valid && $parent_taxon_id)
        {
            $this->synonyms[$parent_taxon_id][] = $row;
        }elseif(!$is_valid && $accepted_taxon_id)
        {
            $this->synonyms[$accepted_taxon_id][] = $row;
        }
    }
    
    function add_hierarchy_entry(&$row, $parent_hierarchy_entry_id, $ancestry)
    {
        static $i = 0;
        if($i % 500 == 0)
        {
            if($GLOBALS['ENV_DEBUG']) echo "Inserting taxon $i: ".memory_get_usage()."\n";
        }
        $i++;
        // make sure this taxon has a name, otherwise skip this branch
        $scientific_name = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/scientificName']);
        if(!$scientific_name) return false;
        
        // this taxon_id has already been inserted meaning this tree has a loop in it - so stop
        $taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        if(!$taxon_id) $taxon_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        if(!$taxon_id) return false;
        if(isset($this->taxon_ids_inserted[$taxon_id])) return false;
        
        $name = Name::find_or_create_by_string($scientific_name);
        if(@!$name->id) return false;
        
        $kingdom = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/kingdom']);
        $phylum = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/phylum']);
        $class = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/class']);
        $order = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/order']);
        $family = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/family']);
        $genus = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/genus']);
        $rank = Rank::find_or_create_by_translated_label(@self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonRank']));
        $source_url = @self::field_decode($row['http://rs.tdwg.org/ac/terms/furtherInformationURL']);
        if(!$source_url) $source_url = @self::field_decode($row['http://purl.org/dc/terms/source']);
        
        // these are the taxa using the adjacency list format
        if(!$parent_hierarchy_entry_id && ($kingdom || $phylum || $class || $order || $family || $genus))
        {
            $params = array("identifier"        => $taxon_id,
                            "source_url"        => $source_url,
                            "kingdom"           => $kingdom,
                            "phylum"            => $phylum,
                            "class"             => $class,
                            "order"             => $order,
                            "family"            => $family,
                            "genus"             => $genus,
                            "scientificName"    => $scientific_name,
                            "name"              => $name,
                            "rank"              => $rank);
            $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($params, $this->harvest_event->resource->hierarchy_id);
            if(@!$hierarchy_entry->id) return;
            $this->taxon_ids_inserted[$taxon_id] = $hierarchy_entry->id;
        }
        // these are the taxa using the parent-child format
        else
        {
            $params = array("identifier"        => $taxon_id,
                            "source_url"        => $source_url,
                            "name_id"           => $name->id,
                            "parent_id"         => $parent_hierarchy_entry_id,
                            "hierarchy_id"      => $this->harvest_event->resource->hierarchy_id,
                            "rank"              => $rank,
                            "ancestry"          => $ancestry);
            $hierarchy_entry = HierarchyEntry::find_or_create_by_array($params);
            if(@!$hierarchy_entry->id) return;
            $this->taxon_ids_inserted[$taxon_id] = $hierarchy_entry->id;
        }
        
        if($name_published_in = @$row['http://rs.tdwg.org/dwc/terms/namePublishedIn'])
        {
            $individual_references = explode("||", $name_published_in);
            foreach($individual_references as $reference_string)
            {
                $reference = Reference::find_or_create_by_full_reference(trim($reference_string));
                if(@$reference->id) $hierarchy_entry->add_reference($reference->id);
            }
        }
        
        if($reference_id = @$row['http://eol.org/schema/media/referenceID'])
        {
            $ref_ids = preg_split("/[;,]/", $reference_id);
            foreach($ref_ids as $ref_id)
            {
                if($ref_id) $this->taxon_reference_ids[$ref_id][$hierarchy_entry->id] = 1;
            }
        }
        
        if(isset($this->synonyms[$taxon_id]))
        {
            foreach($this->synonyms[$taxon_id] as $synonym_row)
            {
                $synonym_scientific_name = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/scientificName']);
                if(!$synonym_scientific_name) continue;
                
                $synonym_taxon_id = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/taxonID']);
                if(!$synonym_taxon_id) $taxon_id = @self::field_decode($synonym_row['http://purl.org/dc/terms/identifier']);
                if(!$synonym_taxon_id) continue;
                if(isset($this->taxon_ids_inserted[$synonym_taxon_id])) continue;
                
                $synonym_name = Name::find_or_create_by_string($synonym_scientific_name);
                if(@!$synonym_name->id) continue;
                
                $taxonomic_status = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/taxonomicStatus']) ?: 'synonym';
                $synonym_relation = SynonymRelation::find_or_create_by_translated_label($taxonomic_status);
                $hierarchy_entry->add_synonym($synonym_name->id, @$synonym_relation->id ?: 0, 0, 0);
                $this->taxon_ids_inserted[$synonym_taxon_id] = 1;
            }
            unset($this->synonyms[$taxon_id]);
        }
        
        if(isset($this->children[$taxon_id]))
        {
            // set the ancestry for its children
            if($ancestry) $this_ancestry = $ancestry ."|". $name->id;
            else $this_ancestry = $name->id;
            
            foreach($this->children[$taxon_id] as $row)
            {
                $this->add_hierarchy_entry($row, $hierarchy_entry->id, $this_ancestry);
            }
            unset($this->children[$taxon_id]);
        }
        unset($hierarchy_entry);
    }
    
    public function insert_references($row)
    {
        $reference_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        // we really only need to insert the references that relate to taxa or media
        if(!isset($this->taxon_reference_ids[$reference_id]) && !isset($this->media_reference_ids[$reference_id])) return;
        
        $full_reference = @self::field_decode($row['http://eol.org/schema/reference/fullReference']);
        $title = @self::field_decode($row['http://purl.org/dc/terms/title']);
        $pages = @self::field_decode($row['http://purl.org/ontology/bibo/pages']);
        $pageStart = @self::field_decode($row['http://purl.org/ontology/bibo/pageStart']);
        $pageEnd = @self::field_decode($row['http://purl.org/ontology/bibo/pageEnd']);
        $volume = @self::field_decode($row['http://purl.org/ontology/bibo/volume']);
        $edition = @self::field_decode($row['http://purl.org/ontology/bibo/edition']);
        $publisher = @self::field_decode($row['http://purl.org/dc/terms/publisher']);
        $authorList = @self::field_decode($row['http://purl.org/ontology/bibo/authorList']);
        $editorList = @self::field_decode($row['http://purl.org/ontology/bibo/editorList']);
        $created = @self::field_decode($row['http://purl.org/dc/terms/created']);
        $language = Language::find_or_create_for_parser(@self::field_decode($row['http://purl.org/dc/terms/language']));
        $uri = @self::field_decode($row['http://purl.org/ontology/bibo/uri']);
        $doi = @self::field_decode($row['http://purl.org/ontology/bibo/doi']);
        
        $params = array("provider_mangaed_id"       => $reference_id,
                        "full_reference"            => $full_reference,
                        "title"                     => $title,
                        "pages"                     => $pages,
                        "page_start"                => $pageStart,
                        "page_end"                  => $pageEnd,
                        "volume"                    => $volume,
                        "edition"                   => $edition,
                        "publisher"                 => $publisher,
                        "authors"                   => $authorList,
                        "editors"                   => $editorList,
                        "publication_created_at"    => $created,
                        "language_id"               => @$language->id ?: 0,
                        "editors"                   => $editorList);
        $reference = Reference::find_or_create($params);
        if($uri)
        {
            $type = RefIdentifierType::find_or_create_by_label('uri');
            $reference->add_ref_identifier(@$type->id ?: 0, $uri);
        }
        if($doi)
        {
            $type = RefIdentifierType::find_or_create_by_label('doi');
            $reference->add_ref_identifier(@$type->id ?: 0, $doi);
        }
        
        
        if(isset($this->taxon_reference_ids[$reference_id]))
        {
            foreach($this->taxon_reference_ids[$reference_id] as $hierarchy_entry_id => $val)
            {
                // TODO: find_or_create doesn't work here because of the dual primary key
                $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs (hierarchy_entry_id, ref_id) VALUES ($hierarchy_entry_id, $reference->id)");
                // HierarchyEntriesRef::find_or_create(array(
                //     'hierarchy_entry_id'    => $hierarchy_entry_id,
                //     'ref_id'                => $reference->id));
            }
        }
        if(isset($this->media_reference_ids[$reference_id]))
        {
            foreach($this->media_reference_ids[$reference_id] as $data_object_id => $val)
            {
                // TODO: find_or_create doesn't work here because of the dual primary key
                $this->mysqli->insert("INSERT IGNORE INTO data_objects_refs (data_object_id, ref_id) VALUES ($data_object_id, $reference->id)");
                // DataObjectsRef::find_or_create(array(
                //     'data_object_id'    => $data_object_id,
                //     'ref_id'            => $reference->id));
            }
        }
    }
    
    public function insert_data_object($row)
    {
        static $i = 0;
        if($i % 500 == 0)
        {
            echo "Inserting DataObject $i\n";
            $this->mysqli->commit();
        }
        $i++;
        if($i>=10) return;
        /*
        # <field index="0" term="http://purl.org/dc/terms/identifier"/>
        -% <field index="1" term="http://rs.tdwg.org/dwc/terms/taxonID"/>
        -% <field index="2" term="http://rs.tdwg.org/dwc/terms/scientificName"/>
        -% <field index="3" term="http://rs.tdwg.org/dwc/terms/vernacularName"/>
        # <field index="4" term="http://purl.org/dc/terms/type"/>
        # <field index="5" term="http://rs.tdwg.org/audubon_core/subtype"/>
        # <field index="6" term="http://purl.org/dc/terms/format"/>
        # <field index="7" term="http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm"/>
        # <field index="8" term="http://purl.org/dc/terms/title"/>
        # <field index="9" term="http://purl.org/dc/terms/description"/>
        # <field index="10" term="http://rs.tdwg.org/ac/terms/accessURI"/>
        # <field index="11" term="http://eol.org/schema/media/thumbnailURL"/>
        # <field index="12" term="http://rs.tdwg.org/ac/terms/furtherInformationURL"/>
        # <field index="13" term="http://ns.adobe.com/xap/1.0/CreateDate"/>
        # <field index="14" term="http://purl.org/dc/terms/modified"/>
        # <field index="15" term="http://purl.org/dc/terms/available"/>
        # <field index="16" term="http://purl.org/dc/terms/language"/>
        # <field index="17" term="http://ns.adobe.com/xap/1.0/Rating"/>
        -% <field index="18" term="http://purl.org/dc/terms/audience"/>
        # <field index="19" term="http://ns.adobe.com/xap/1.0/rights/UsageTerms"/>
        # <field index="20" term="http://purl.org/dc/terms/rights"/>
        # <field index="21" term="http://ns.adobe.com/xap/1.0/rights/Owner"/>
        # <field index="22" term="http://purl.org/dc/terms/bibliographicCitation"/>
        ? <field index="23" term="http://purl.org/dc/terms/publisher"/>
        ? <field index="24" term="http://purl.org/dc/terms/contributor"/>
        ? <field index="25" term="http://purl.org/dc/terms/creator"/>
        ? <field index="26" term="http://purl.org/dc/terms/provider"/>
        -% <field index="27" term="http://rs.tdwg.org/ac/terms/subjectPart"/>
        -% <field index="28" term="http://rs.tdwg.org/dwc/terms/lifeStage"/>
        -% <field index="29" term="http://rs.tdwg.org/ac/terms/subjectOrientation"/>
        # <field index="30" term="http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated"/>
        # <field index="31" term="http://www.w3.org/2003/01/geo/wgs84_pos#lat"/>
        # <field index="32" term="http://www.w3.org/2003/01/geo/wgs84_pos#long"/>
        # <field index="33" term="http://www.w3.org/2003/01/geo/wgs84_pos#alt"/>
        -% <field index="34" term="http://eol.org/schema/media/referenceID"/>
        */
        
        
        $taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        if($taxon_id)
        {
            $hierarchy_entry_for_this_object = @$this->taxon_hierarchy_entry_ids[$taxon_id];
            if(!$hierarchy_entry_for_this_object) return false;
        }elseif(!$taxon_id)
        {
            $scientific_name = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/scientificName']);
            if(!$scientific_name) return false;
            $taxon_parameters = array("scientific_name" => $scientific_name);
            $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($taxon_parameters, $this->resource->hierarchy_id);
            if(@!$hierarchy_entry->id) return false;
            $this->resource->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
        }
        
        $data_object = new DataObject();
        $data_object->identifier = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        $data_object->data_type = DataType::find_or_create_by_schema_value(@self::field_decode($row['http://purl.org/dc/terms/type']));
        if($dt = DataType::find_or_create_by_schema_value(@self::field_decode($row['http://rs.tdwg.org/audubon_core/subtype'])))
        {
            $data_object->data_subtype_id = $dt->id;
        }
        $data_object->mime_type = MimeType::find_or_create_by_translated_label(@self::field_decode($row['http://purl.org/dc/terms/format']));
        $data_object->object_created_at = @self::field_decode($row['http://ns.adobe.com/xap/1.0/CreateDate']);
        $data_object->object_modified_at = @self::field_decode($row['http://purl.org/dc/terms/modified']);
        $data_object->available_at = @self::field_decode($row['http://purl.org/dc/terms/available']);
        $data_object->object_title = @self::field_decode($row['http://purl.org/dc/terms/title']);
        $data_object->language = Language::find_or_create_for_parser(@self::field_decode($row['http://purl.org/dc/terms/language']));
        $data_object->license = License::find_or_create_for_parser(@self::field_decode($row['http://ns.adobe.com/xap/1.0/rights/UsageTerms']));
        $data_object->rights_statement = @self::field_decode($row['http://purl.org/dc/terms/rights']);
        $data_object->rights_holder = @self::field_decode($row['http://ns.adobe.com/xap/1.0/rights/Owner']);
        $data_object->bibliographic_citation = @self::field_decode($row['http://purl.org/dc/terms/bibliographicCitation']);
        $data_object->source_url = @self::field_decode($row['http://rs.tdwg.org/ac/terms/furtherInformationURL']);
        $data_object->description = @self::field_decode($row['http://purl.org/dc/terms/description']);
        // Turn newlines into paragraphs
        $data_object->description = str_replace("\n","</p><p>", $data_object->description);
        
        $data_object->object_url = @self::field_decode($row['http://rs.tdwg.org/ac/terms/accessURI']);
        $data_object->thumbnail_url = @self::field_decode($row['http://eol.org/schema/media/thumbnailURL']);
        $data_object->location = @self::field_decode($row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated']);
        $data_object->latitude = @self::field_decode($row['http://www.w3.org/2003/01/geo/wgs84_pos#lat']);
        $data_object->longitude = @self::field_decode($row['http://www.w3.org/2003/01/geo/wgs84_pos#long']);
        $data_object->altitude = @self::field_decode($row['http://www.w3.org/2003/01/geo/wgs84_pos#alt']);
        
        
        $rating = @self::field_decode($row['http://ns.adobe.com/xap/1.0/Rating']);
        if((is_numeric($rating)) && $rating > 0 && $rating <= 5) $data_object->data_rating = $rating;
        
        
        
        //TODO - update this
        if($data_object->mime_type && $data_object->mime_type->equals(MimeType::flash()) && $data_object->is_video())
        {
            $data_object->data_type = DataType::youtube();
            $data_object->data_type_id = DataType::youtube()->id;
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
        
        
        if($subject = @self::field_decode($row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']))
        {
            $data_object_parameters["info_items"] = array(InfoItem::find_or_create_by_schema_value($subject));
        }
        
        
        // //take the taxon's source_url if none present
        // if(!@$data_object->source_url && @$taxon_parameters["source_url"]) $data_object->source_url = $taxon_parameters["source_url"];
        // 
        
        
        /* Checking requirements */
        // if text: must have description
        if($data_object->data_type->equals(DataType::text()) && !$data_object->description) return;
        // if image, movie or sound: must have object_url
        if(($data_object->data_type->equals(DataType::video()) || $data_object->data_type->equals(DataType::sound()) || $data_object->data_type->equals(DataType::image())) && !$data_object->object_url) return;
        
        print_r($data_object);
        
        
        list($data_object, $status) = DataObject::find_and_compare($this->harvest_event->resource, $data_object, $this->content_manager);
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
        return trim($string);
    }
}

?>