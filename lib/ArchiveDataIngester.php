<?php
namespace php_active_record;

class ArchiveDataIngester
{
    private $resource;
    private static $valid_taxonomic_statuses = array("valid", "accepted", "accepted name", "provisionally accepted name", "current");

    public function __construct($harvest_event)
    {
        $this->harvest_event = $harvest_event;
        $this->mysqli =& $GLOBALS['db_connection'];
        $this->sparql_client = SparqlClient::connection();
    }

    public function parse($validate = true, &$archive_reader = null, &$archive_validator = null)
    {
      $this->harvest_event->debug_start("ADI/parse");
        if(!is_dir($this->harvest_event->resource->archive_path())) {
          debug("ERROR - attempt to parse a resource with no archive");
          $this->harvest_event->debug_end("ADI/parse");
          return false;
        }
        if($archive_reader) $this->archive_reader = $archive_reader;
        else $this->archive_reader = new ContentArchiveReader(null, $this->harvest_event->resource->archive_path());
        if($archive_validator) $this->archive_validator = $archive_validator;
        else $this->archive_validator = new ContentArchiveValidator($this->archive_reader, $this->harvest_event->resource);
        $this->content_manager = new ContentManager();

        // set valid to true if we don't need validation
        $valid = $validate ? $this->archive_validator->is_valid() : true;
        if($valid !== true) {
          debug("ERROR - resource INVALID");
          $this->harvest_event->debug_end("ADI/parse");
          write_to_resource_harvesting_log(implode(",", $this->archive_validator->structural_errors()));
          return array_merge($this->archive_validator->structural_errors(), $this->archive_validator->display_errors());
        }
        // even if we don't want to validate - we need the errors to determine
        // which rows to ignore
        // TODO: sooooo... we should be storing validations somewhere we can
        // retrieve it without re-validating every time.
        if(!$validate) $this->archive_validator->get_validation_errors(true);
        $this->archive_validator->delete_validation_cache();

        $this->taxon_reference_ids = array();
        $this->media_reference_ids = array();
        $this->media_agent_ids = array();
        $this->media_ids_inserted = array();
        $this->occurrence_ids_inserted = array();
        // map occurrence_id to taxon_id
        $this->occurrence_taxon_mapping = array();
        $this->event_ids_inserted = array();

        /* During harvesting we need to delete all the old records associated
        /* with HierarchyEntries and DataObjects so we can add the new ones, and
        /* also properly represent the case where a provider deletes a common
        /* name or reference from an object or taxon
        */
        $this->object_references_deleted = array();
        $this->entry_references_deleted = array();
        $this->entry_vernacular_names_deleted = array();
        $this->entry_synonyms_deleted = array();

        $this->dataset_metadata = array();
        $this->collect_dataset_attribution();

        $this->harvest_event->debug_start("ADI/transaction");
        $this->mysqli->begin_transaction();
        $this->start_reading_taxa();
        $this->mysqli->commit();
        $this->archive_reader->process_row_type("http://rs.gbif.org/terms/1.0/VernacularName", array($this, 'insert_vernacular_names'));
        $this->archive_reader->process_row_type("http://eol.org/schema/media/Document", array($this, 'insert_data_object'));
        $this->archive_reader->process_row_type("http://eol.org/schema/reference/Reference", array($this, 'insert_references'));
        $this->archive_reader->process_row_type("http://eol.org/schema/agent/Agent", array($this, 'insert_agents'));
        $this->archive_reader->process_row_type("http://rs.gbif.org/terms/1.0/Reference", array($this, 'insert_gbif_references'));
        $this->archive_reader->process_row_type("http://rs.tdwg.org/dwc/terms/Occurrence", array($this, 'insert_data'));
        $this->archive_reader->process_row_type("http://rs.tdwg.org/dwc/terms/MeasurementOrFact", array($this, 'insert_data'));
        $this->archive_reader->process_row_type("http://eol.org/schema/Association", array($this, 'insert_data'));
        $this->archive_reader->process_row_type("http://rs.tdwg.org/dwc/terms/Event", array($this, 'insert_data'));
        $this->sparql_client->insert_remaining_bulk_data();

        $this->mysqli->end_transaction();
        $this->harvest_event->debug_end("ADI/transaction");

        $this->harvest_event->debug_end("ADI/parse");
        // returning true so we know that the parsing/ingesting succeeded
        return true;
    }

    public function start_reading_taxa()
    {
      $this->harvest_event->debug_start("ADI/start_reading_taxa");
        $this->children = array();
        $this->synonyms = array();
        $this->archive_reader->process_row_type("http://rs.tdwg.org/dwc/terms/Taxon", array($this, 'read_taxon'));
        $this->begin_adding_taxa();
        $this->harvest_event->debug_end("ADI/start_reading_taxa");
    }

    private function begin_adding_taxa()
    {
      $this->harvest_event->debug_start("ADI/begin_adding_taxa");
        $this->taxon_ids_inserted = array();
        if(isset($this->children[0]))
        {
            // get all the roots, or taxa with no parents
            foreach($this->children[0] as &$row)
            {
                $parent_hierarchy_entry_id = 0;
                $ancestry = "";
                self::uncompress_array($row);
                $this->add_hierarchy_entry($row, $parent_hierarchy_entry_id, $ancestry, @$row['http://rs.tdwg.org/dwc/terms/scientificName']);
            }
        } else {
          debug("ERROR: no root taxa!");
          echo "THERE ARE NO ROOT TAXA\nAborting import\n";
        }
        $this->harvest_event->debug_end("ADI/begin_adding_taxa");
    }

    public function read_taxon($row, $parameters)
    {
        self::debug_iterations("Loading taxon", 5000);

        $taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        if(!$taxon_id) $taxon_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        else unset($row['http://purl.org/dc/terms/identifier']);
        $parent_taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/parentNameUsageID']);
        $accepted_taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']);
        $kingdom = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/kingdom']);
        $is_valid = self::check_taxon_validity($row);
        if($parent_taxon_id && strtolower($kingdom) != 'viruses')
        {
            unset($row['http://rs.tdwg.org/dwc/terms/kingdom']);
            unset($row['http://rs.tdwg.org/dwc/terms/phylum']);
            unset($row['http://rs.tdwg.org/dwc/terms/class']);
            unset($row['http://rs.tdwg.org/dwc/terms/order']);
            unset($row['http://rs.tdwg.org/dwc/terms/superfamily']);
            unset($row['http://rs.tdwg.org/dwc/terms/family']);
            unset($row['http://rs.tdwg.org/dwc/terms/genus']);
            unset($row['http://rs.tdwg.org/dwc/terms/subgenus']);
        }
        unset($row['http://rs.tdwg.org/dwc/terms/datasetName']);
        unset($row['http://rs.tdwg.org/dwc/terms/taxonConceptID']);
        $row['archive_line_number'] = $parameters['archive_line_number'];
        $row['archive_file_location'] = $parameters['archive_table_definition']->location;

        // TODO: what is compress_array? I am assuming it's like Ruby's #compact
        self::compress_array($row);
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

    function add_hierarchy_entry(&$row, $parent_hierarchy_entry_id, $ancestry, $branch_kingdom)
    {
        self::debug_iterations("Inserting taxon");
        self::commit_iterations("Taxa", 500);
        if($this->archive_validator->has_error_by_line('http://rs.tdwg.org/dwc/terms/taxon', $row['archive_file_location'], $row['archive_line_number']))
        {
        	write_to_resource_harvesting_log("ERROR: add_hierarchy_entry: has_error_by_line" . ",file_location:" . $row['archive_file_location'] . ",line_number:" . $row['archive_line_number']);
        	return false;
        }

        // make sure this taxon has a name, otherwise skip this branch
        $scientific_name = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/scientificName']);
        $authorship = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/scientificNameAuthorship']);
        $kingdom = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/kingdom']);
        $genus = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/genus']);
        $rank_label = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonRank']);
        // COL exception
        if(strtolower($kingdom) == 'viruses')
        {
            if(substr($scientific_name, -1) == ":") $scientific_name = substr($scientific_name, 0, -1);
            if(preg_match("/^(.*) ICTV$/i", $scientific_name, $arr)) $scientific_name = $arr[1];
        }
        // COL exception
        if(strtolower($kingdom) == 'viruses' && $genus && strtolower($rank_label) != 'genus')
        {
            if(stripos($scientific_name, $genus) == 0)
            {
                $scientific_name = ucfirst(trim(substr($scientific_name, strlen($genus))));
            }
        }else
        {
            if($authorship && stripos($scientific_name, $authorship) === false) $scientific_name = trim($scientific_name ." ". $authorship);
        }
        if(!$scientific_name) return false;

        $taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        if(!$taxon_id) $taxon_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        if(!$taxon_id) {
          debug("ERROR - no taxon ID for $scientific_name, skipping");
          return false;
        }
        if(isset($this->taxon_ids_inserted[$taxon_id])) {
          // this taxon_id has already been inserted meaning this tree has a loop in it - so stop
          debug("ERROR - taxon ID ($taxon_id) for $scientific_name already inserted; LOOP?");
          return false;
        }

        $scientific_name = ucfirst($scientific_name);
        $name = Name::find_or_create_by_string($scientific_name);
        if(@!$name->id) {
          debug("ERROR - Failed to insert name: $scientific_name");
          return false;
        }

        $phylum = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/phylum']);
        $class = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/class']);
        $order = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/order']);
        $family = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/family']);
        $rank = Rank::find_or_create_by_translated_label($rank_label);
        $dataset_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/datasetID']);
        $taxonomic_status = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonomicStatus']);
        $source_url = @self::field_decode($row['http://rs.tdwg.org/ac/terms/furtherInformationURL']);
        if(!$source_url) $source_url = @self::field_decode($row['http://purl.org/dc/terms/source']);
        if(!$source_url) $source_url = @self::field_decode($row['http://purl.org/dc/terms/references']);
        if(!$source_url) $source_url = @self::field_decode($row['http://purl.org/dc/terms/isReferencedBy']);
        if(isset($row['http://rs.tdwg.org/dwc/terms/taxonRemarks'])) $taxon_remarks = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonRemarks']);
        else $taxon_remarks = NULL;
        if(!$taxon_remarks && strtolower($taxonomic_status) == 'provisionally accepted name') $taxon_remarks = "provisionally accepted name";

        // TODO: This block is somewhat confusing. Clearly, it's clearing the
        // rank that's currently being read, but shouldn't it also clear all of
        // the ranks below that?
        if(strtolower($rank_label) == 'kingdom') $kingdom = null;
        if(strtolower($rank_label) == 'phylum') $phylum = null;
        if(strtolower($rank_label) == 'class') $class = null;
        if(strtolower($rank_label) == 'order') $order = null;
        if(strtolower($rank_label) == 'family') $family = null;
        if(strtolower($rank_label) == 'genus') $genus = null;

        // these are the taxa using the adjacency list format
        if(!$parent_hierarchy_entry_id && ($kingdom || $phylum || $class || $order || $family || $genus))
        {
            $params = array("identifier"        => $taxon_id,
                            "source_url"        => $source_url,
                            "kingdom"           => ucfirst($kingdom),
                            "phylum"            => ucfirst($phylum),
                            "class"             => ucfirst($class),
                            "order"             => ucfirst($order),
                            "family"            => ucfirst($family),
                            "genus"             => ucfirst($genus),
                            "scientificName"    => ucfirst($scientific_name),
                            "name"              => $name,
                            "rank"              => $rank,
                            "taxon_remarks"     => $taxon_remarks);
            $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($params, $this->harvest_event->resource->hierarchy_id);
            if(@!$hierarchy_entry->id) {
              debug("ERROR - unable to insert hierarchy entry for $scientific_name");
              return;
            }
            // NOTE: This is NOT adding a hierarchy entry, but a
            // harvest_event_hierarchy_entry:
            // TODO: I am not sure this adds entries for ancestors!
            $this->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
            $this->taxon_ids_inserted[$taxon_id] = array('hierarchy_entry_id' => $hierarchy_entry->id, 'taxon_concept_id' => $hierarchy_entry->taxon_concept_id, 'source_url' => $source_url);
            self::compress_array($this->taxon_ids_inserted[$taxon_id]);
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
                            "ancestry"          => $ancestry,
                            "taxon_remarks"     => $taxon_remarks);
            $hierarchy_entry = HierarchyEntry::find_or_create_by_array($params);
            if(@!$hierarchy_entry->id) return;
            $this->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
            $this->taxon_ids_inserted[$taxon_id] = array('hierarchy_entry_id' => $hierarchy_entry->id, 'taxon_concept_id' => $hierarchy_entry->taxon_concept_id, 'source_url' => $source_url);
            self::compress_array($this->taxon_ids_inserted[$taxon_id]);
        }

        if(!isset($this->entry_references_deleted[$hierarchy_entry->id]))
        {
            $hierarchy_entry->delete_refs();
            $this->entry_references_deleted[$hierarchy_entry->id] = true;
        }
        if(!isset($this->entry_vernacular_names_deleted[$hierarchy_entry->id]))
        {
            $this->mysqli->delete("DELETE FROM synonyms WHERE hierarchy_entry_id=$hierarchy_entry->id AND hierarchy_entry_id=$hierarchy_entry->id AND hierarchy_id=". $this->harvest_event->resource->hierarchy_id ." AND language_id!=0 AND language_id!=". Language::find_or_create_for_parser('scientific name')->id);
            $this->entry_vernacular_names_deleted[$hierarchy_entry->id] = true;
        }
        if(!isset($this->entry_synonyms_deleted[$hierarchy_entry->id]))
        {
            $hierarchy_entry->delete_synonyms();
            $this->entry_synonyms_deleted[$hierarchy_entry->id] = true;
        }

        if($name_published_in = @$row['http://rs.tdwg.org/dwc/terms/namePublishedIn'])
        {
            $individual_references = explode("||", $name_published_in);
            foreach($individual_references as $reference_string)
            {
                $reference = Reference::find_or_create_by_full_reference(trim($reference_string));
                if(@$reference->id)
                {
                    $hierarchy_entry->add_reference($reference->id);
                    $this->mysqli->query("UPDATE refs SET published=1, visibility_id=".Visibility::visible()->id." WHERE id=$reference->id");
                }
            }
        }

        // keep track of reference foreign keys
        self::append_foreign_keys_from_row($row, 'http://eol.org/schema/reference/referenceID', $this->taxon_reference_ids, $hierarchy_entry->id);

        if(isset($this->synonyms[$taxon_id]))
        {
            foreach($this->synonyms[$taxon_id] as $synonym_row)
            {
                self::uncompress_array($synonym_row);
                $synonym_scientific_name = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/scientificName']);
                $synonym_authorship = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/scientificNameAuthorship']);
                if($synonym_authorship && stripos($synonym_scientific_name, $synonym_authorship) === false)
                {
                    $synonym_scientific_name = trim($synonym_scientific_name ." ". $synonym_authorship);
                }
                if(!$synonym_scientific_name) continue;

                $synonym_taxon_id = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/taxonID']);
                if(!$synonym_taxon_id) $taxon_id = @self::field_decode($synonym_row['http://purl.org/dc/terms/identifier']);
                if(!$synonym_taxon_id) continue;

                $synonym_name = Name::find_or_create_by_string(ucfirst($synonym_scientific_name));
                if(@!$synonym_name->id) continue;

                $taxonomic_status = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/taxonomicStatus']) ?: 'synonym';
                if(isset($synonym_row['http://rs.tdwg.org/dwc/terms/taxonRemarks'])) $taxon_remarks = @self::field_decode($synonym_row['http://rs.tdwg.org/dwc/terms/taxonRemarks']);
                else $taxon_remarks = NULL;

                $synonym_relation = SynonymRelation::find_or_create_by_translated_label($taxonomic_status);
                $hierarchy_entry->add_synonym($synonym_name->id, @$synonym_relation->id ?: 0, 0, 0, 0, 0, $taxon_remarks);
            }
            unset($this->synonyms[$taxon_id]);
        }

        // COL exception
        if($dataset_id && isset($this->dataset_metadata[$dataset_id]) && $metadata = $this->dataset_metadata[$dataset_id])
        {
            $hierarchy_entry->delete_agents();
            $agent_name = $metadata['title'];
            if($editors = $metadata['editors']) $agent_name .= " by $editors";
            $params = array("full_name" => $agent_name,
                            "agent_role" => AgentRole::find_or_create_by_translated_label('Source'));
            $agent = Agent::find_or_create($params);
            $hierarchy_entry->add_agent($agent->id, @$a['agent_role']->id ?: 0, 0);

            $reference = Reference::find_or_create(array("full_reference" => $metadata['citation']));

            $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs (hierarchy_entry_id, ref_id) VALUES ($hierarchy_entry->id, $reference->id)");
            $this->mysqli->query("UPDATE refs SET published=1, visibility_id=".Visibility::visible()->id." WHERE id=$reference->id");
        }

        $parameters = array('archive_table_definition' => (object) array('row_type' => 'http://rs.tdwg.org/dwc/terms/Taxon'));
        $this->insert_data($row, $parameters);

        if(isset($this->children[$taxon_id]))
        {
            // set the ancestry for its children
            if($ancestry) $this_ancestry = $ancestry ."|". $name->id;
            else $this_ancestry = $name->id;

            foreach($this->children[$taxon_id] as &$row)
            {
                self::uncompress_array($row);
                $this->add_hierarchy_entry($row, $hierarchy_entry->id, $this_ancestry, $branch_kingdom);
            }
            unset($this->children[$taxon_id]);
        }
        unset($hierarchy_entry);
        unset($row);
    }

    public function insert_vernacular_names($row, $parameters)
    {
        self::debug_iterations("Inserting VernacularName");
        $this->commit_iterations("VernacularName", 500);
        if($this->archive_validator->has_error_by_line('http://rs.gbif.org/terms/1.0/vernacularname', $parameters['archive_table_definition']->location, $parameters['archive_line_number']))
        {
        	write_to_resource_harvesting_log("ERROR: insert_vernacular_names: has_error_by_line" . ",file_location:" . $parameters['archive_table_definition']->location . ",line_number:" . $parameters['archive_line_number']);
        	return false;
        }

        $taxon_ids = self::get_foreign_keys_from_row($row, 'http://rs.tdwg.org/dwc/terms/taxonID');
        $taxon_info = array();
        if($taxon_ids)
        {
            foreach($taxon_ids as $taxon_id)
            {
                if($taxon_info = @$this->taxon_ids_inserted[$taxon_id])
                {
                    self::uncompress_array($taxon_info);
                    $taxon_info[] = $taxon_info;
                }
            }
        }
        if(!$taxon_info) return false;

        $vernacularName = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/vernacularName']);
        $source = @self::field_decode($row['http://purl.org/dc/terms/source']);
        $languageString = @self::field_decode($row['http://purl.org/dc/terms/language']);
        $locality = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/locality']);
        $countryCode = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/countryCode']);
        $isPreferredName = @self::field_decode($row['http://rs.gbif.org/terms/1.0/isPreferredName']);
        $taxonRemarks = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonRemarks']);

        $name = Name::find_or_create_by_string($vernacularName);
        $language = Language::find_or_create_for_parser($languageString);
        if(!$name) return false;

        foreach($taxon_info as $info)
        {
            $he_id = $taxon_info['hierarchy_entry_id'];
            $tc_id = $taxon_info['taxon_concept_id'];
            $common_name_relation = SynonymRelation::find_or_create_by_translated_label('common name');
            $result = $this->mysqli->query(
              "SELECT SQL_NO_CACHE id FROM synonyms" .
              " WHERE name_id = " . $name->id .
              " AND synonym_relation_id = " . $common_name_relation->id .
              " AND hierarchy_entry_id = " . $he_id .
              " AND hierarchy_id = " .
              $this->harvest_event->resource->hierarchy_id .
              " AND identifier = '". $taxon_ids[0] . "'");
            if ($result && $result->fetch_assoc()){
              $l_id = @$language->id ?: 0;
              $GLOBALS['db_connection']->update(
                "UPDATE synonyms SET" .
                " language_id = " . $l_id .
                ", published = 0" .
                ", taxon_remarks = '" . $taxonRemarks .
                "' WHERE name_id = " . $name->id .
                " AND synonym_relation_id = " . $common_name_relation->id .
                " AND hierarchy_entry_id = " . $he_id .
                " AND hierarchy_id = " .
                $this->harvest_event->resource->hierarchy_id .
                "AND identifier = '". $taxon_ids[0] . "'");
              break;
            }else{
                Synonym::find_or_create(array('name_id'               => $name->id,
                                              'synonym_relation_id'   => $common_name_relation->id,
                                              'language_id'           => @$language->id ?: 0,
                                              'hierarchy_entry_id'    => $he_id,
                                              'preferred'             => ($isPreferredName != ''),
                                              'hierarchy_id'          => $this->harvest_event->resource->hierarchy_id,
                                              'vetted_id'             => 0,
                                              'published'             => 0,
                                              'taxonRemarks'          => $taxonRemarks,
                                              'identifier'            => $taxon_ids[0] ));
                 break;
            }
        }
    }

    public function insert_data_object($row, $parameters)
    {
        self::debug_iterations("Inserting DataObject");
        $this->commit_iterations("DataObject", 20);
        if($this->archive_validator->has_error_by_line('http://eol.org/schema/media/document', $parameters['archive_table_definition']->location, $parameters['archive_line_number']))
        {
        	write_to_resource_harvesting_log("ERROR: insert_data_object: has_error_by_line" . ",file_location:" . $parameters['archive_table_definition']->location . ",line_number:" . $parameters['archive_line_number']);
        	return false;
        }

        $object_taxon_ids = self::get_foreign_keys_from_row($row, 'http://rs.tdwg.org/dwc/terms/taxonID');
        $object_taxon_info = array();
        if($object_taxon_ids)
        {
            foreach($object_taxon_ids as $taxon_id)
            {
                if($taxon_info = @$this->taxon_ids_inserted[$taxon_id])
                {
                    self::uncompress_array($taxon_info);
                    $object_taxon_info[] = $taxon_info;
                }
            }
        }

        if(!$object_taxon_info) return false;

        if($this->harvest_event->resource->is_eol_flickr_group() && self::is_this_flickr_image_in_inaturalist($row)) return false;

        $data_object = new DataObject();
        $data_object->identifier = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        if(isset($this->media_ids_inserted[$data_object->identifier])) return false;

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

        // check multiple fields for a value of license
        if(isset($row['http://purl.org/dc/terms/license']))
        {
            $license_string = @self::field_decode($row['http://purl.org/dc/terms/license']);
        }else $license_string = @self::field_decode($row['http://ns.adobe.com/xap/1.0/rights/UsageTerms']);
        // convert British licences to American licenses
        $license_string = str_replace("creativecommons.org/licences/", "creativecommons.org/licenses/", $license_string);
        if(!$license_string && $this->harvest_event->resource->license && $this->harvest_event->resource->license->source_url)
        {
            $license_string = $this->harvest_event->resource->license->source_url;
        }
        if(!$license_string || !\eol_schema\MediaResource::valid_license($license_string)) return false;
        $data_object->license = License::find_or_create_for_parser($license_string);

        $data_object->rights_statement = @self::field_decode($row['http://purl.org/dc/terms/rights']);
        $data_object->rights_holder = @self::field_decode($row['http://ns.adobe.com/xap/1.0/rights/Owner']);
        $data_object->bibliographic_citation = @self::field_decode($row['http://purl.org/dc/terms/bibliographicCitation']);
        $data_object->source_url = @self::field_decode($row['http://rs.tdwg.org/ac/terms/furtherInformationURL']);
        $data_object->derived_from = @self::field_decode($row['http://rs.tdwg.org/ac/terms/derivedFrom']);
        $data_object->description = @self::field_decode($row['http://purl.org/dc/terms/description']);
        // Turn newlines into paragraphs
        $data_object->description = str_replace("\n","</p><p>", $data_object->description);

        $data_object->object_url = @self::field_decode($row['http://rs.tdwg.org/ac/terms/accessURI']);
        $data_object->thumbnail_url = @self::field_decode($row['http://eol.org/schema/media/thumbnailURL']);
        $data_object->location = @self::field_decode($row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated']);
        $data_object->spatial_location = @self::field_decode($row['http://purl.org/dc/terms/spatial']);
        $data_object->latitude = @self::field_decode($row['http://www.w3.org/2003/01/geo/wgs84_pos#lat']);
        $data_object->longitude = @self::field_decode($row['http://www.w3.org/2003/01/geo/wgs84_pos#long']);
        $data_object->altitude = @self::field_decode($row['http://www.w3.org/2003/01/geo/wgs84_pos#alt']);

        $rating = @self::field_decode($row['http://ns.adobe.com/xap/1.0/Rating']);
        // ratings may be 0 to 5
        // TODO: technically 0 means untrusted, and then anywhere from 1-5 is OK.
        // 0.5 for example isn't really valid acording to the schema
        if((is_numeric($rating)) && $rating > 0 && $rating <= 5) $data_object->data_rating = $rating;

        //TODO - update this
        if($data_object->mime_type && $data_object->mime_type->equals(MimeType::flash()) && $data_object->is_video())
        {
            $data_object->data_type = DataType::youtube();
            $data_object->data_type_id = DataType::youtube()->id;
        }

        // //take the first available source_url of one of this object's taxa
        if(!@$data_object->source_url && @$taxon_parameters["source_url"])
        {
            foreach($object_taxon_info as $taxon_info)
            {
                if($source_url = $taxon_info['source_url'])
                {
                    $data_object->source_url = $source_url;
                    break;
                }
            }
        }

        /* Checking requirements */
        // if text: must have description
        if($data_object->data_type->equals(DataType::text()) && !$data_object->description) return false;
        // if image, movie or sound: must have object_url
        if(($data_object->data_type->equals(DataType::video()) || $data_object->data_type->equals(DataType::sound()) || $data_object->data_type->equals(DataType::image())) && !$data_object->object_url) return false;



        /* ADDING THE DATA OBJECT */
        list($data_object, $status) = DataObject::find_and_compare($this->harvest_event->resource, $data_object, $this->content_manager);
        if(@!$data_object->id) return false;

        $this->media_ids_inserted[$data_object->identifier] = $data_object->id;

        $this->harvest_event->add_data_object($data_object, $status);

        $data_object->delete_hierarchy_entries();
        $vetted_id = Vetted::unknown()->id;
        $visibility_id = Visibility::preview()->id;
        foreach($object_taxon_info as $taxon_info)
        {
            $he_id = $taxon_info['hierarchy_entry_id'];
            $tc_id = $taxon_info['taxon_concept_id'];
            $this->mysqli->insert("INSERT IGNORE INTO data_objects_hierarchy_entries (hierarchy_entry_id, data_object_id, vetted_id, visibility_id) VALUES ($he_id, $data_object->id, $vetted_id, $visibility_id)");
            $this->mysqli->insert("INSERT IGNORE INTO data_objects_taxon_concepts (taxon_concept_id, data_object_id) VALUES ($tc_id, $data_object->id)");
        }











        // a few things to add after the DataObject is inserted

        // keep track of reference foreign keys
        self::append_foreign_keys_from_row($row, 'http://eol.org/schema/reference/referenceID', $this->media_reference_ids, $data_object->id, $data_object->guid);
        // keep track of agent foreign keys
        self::append_foreign_keys_from_row($row, 'http://eol.org/schema/agent/agentID', $this->media_agent_ids, $data_object->id);

        $data_object->delete_info_items();
        $data_object->delete_table_of_contents();
        if($s = @self::field_decode($row['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm']))
        {
            $ii = InfoItem::find_or_create_by_schema_value($s);
            $data_object->add_info_item($ii->id);
            unset($ii);
        }

        if($a = @self::field_decode($row['http://purl.org/dc/terms/audience']))
        {
            $a = Audience::find_or_create_by_translated_label(trim((string) $a));
            $data_object->add_audience($a->id);
            unset($a);
        }




        $data_object_parameters["agents"] = array();
        self::append_agents($row, $data_object_parameters, 'http://purl.org/dc/terms/creator', 'Creator');
        self::append_agents($row, $data_object_parameters, 'http://purl.org/dc/terms/publisher', 'Publisher');
        self::append_agents($row, $data_object_parameters, 'http://purl.org/dc/terms/contributor', 'Contributor');

        $data_object->delete_agents();
        $i = 0;
        foreach($data_object_parameters['agents'] as &$a)
        {
            $agent = Agent::find_or_create($a);
            if($agent->logo_url && !$agent->logo_cache_url)
            {
                if($logo_cache_url = $this->content_manager->grab_file($agent->logo_url, "partner"))
                {
                    $agent->logo_cache_url = $logo_cache_url;
                    $agent->save();
                }
            }

            $data_object->add_agent($agent->id, @$a['agent_role']->id ?: 0, $i);
            unset($a);
            $i++;
        }

        if(!isset($this->object_references_deleted[$data_object->id]))
        {
            $data_object->delete_refs();
            $this->object_references_deleted[$data_object->id] = true;
        }

        // add data object info to resource contribution
        if ($status != "Unchanged")
        {
	        $result = $this->mysqli->query("SELECT id, source_url, taxon_concept_id, hierarchy_id, identifier FROM hierarchy_entries inner join  data_objects_hierarchy_entries on hierarchy_entries.id = data_objects_hierarchy_entries.hierarchy_entry_id where data_object_id =". $data_object->id);
	        if($result && $row=$result->fetch_assoc())
	        {
	             $hierarchy_entry_id = $row["id"];
               $source = "'" . $this->get_hierarchy_entry_outlink($row["hierarchy_id"],
                   $row["identifier"],
                   preg_replace('/\'/', "\\'", $row["source_url"])) . "'";
	             $identifier = "'" . $row["identifier"] . "'";
	             $taxon_concept_id = $row["taxon_concept_id"];
	        }
	        $resource_id = $this->harvest_event->resource_id;
	        $this->mysqli->insert("INSERT IGNORE INTO resource_contributions (resource_id, data_object_id, data_point_uri_id, hierarchy_entry_id, taxon_concept_id, source, object_type, identifier, data_object_type) VALUES ($resource_id, $data_object->id, NULL, $hierarchy_entry_id, $taxon_concept_id, $source, 'data_object', $identifier, $data_object->data_type_id)");
        }
    }

    private function get_hierarchy_entry_outlink($hierarchy_id, $identifier, $source)
    {
        $result = $this->mysqli->query("SELECT outlink_uri FROM hierarchies where id =". $hierarchy_id);
        if($result && $row=$result->fetch_assoc())
        {
        	$outlink_url = $row["outlink_uri"];
        }
    	if(!empty($source))
    	{
    		return preg_replace('~&oldid=[0-9]+$~', '', $source);
    	}
    	else if(isset($hierarchy_id) && !empty($outlink_url))
    	{
    		$matches = preg_match('/%%ID%%/', $outlink_url);
    		if(isset($matches))
    		{
    			if(isset($identifier))
    				return preg_replace('~%%ID%%~', $identifier, $outlink_url);
    		}
    		else {
    			return $outlink_url;
    		}

    	}
    }


    public function insert_references($row, $parameters)
    {
        self::debug_iterations("Inserting reference");
        $this->commit_iterations("Reference", 500);
        if($this->archive_validator->has_error_by_line('http://eol.org/schema/reference/reference', $parameters['archive_table_definition']->location, $parameters['archive_line_number']))
        {
        	write_to_resource_harvesting_log("ERROR: insert_references: has_error_by_line" . ",file_location:" . $parameters['archive_table_definition']->location . ",line_number:" . $parameters['archive_line_number']);
        	return false;
        }

        $reference_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        $reference_taxon_ids = self::get_foreign_keys_from_row($row, 'http://rs.tdwg.org/dwc/terms/taxonID');
        $reference_taxon_info = array();
        if($reference_taxon_ids)
        {
            foreach($reference_taxon_ids as $taxon_id)
            {
                if($taxon_info = @$this->taxon_ids_inserted[$taxon_id])
                {
                    self::uncompress_array($taxon_info);
                    $reference_taxon_info[] = $taxon_info;
                }
            }
        }

        $full_reference = @self::field_decode($row['http://eol.org/schema/reference/full_reference']);
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
                        "publication_created_at"    => @$created ?: '0000-00-00 00:00:00',
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
                $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs (hierarchy_entry_id, ref_id) VALUES ($hierarchy_entry_id, $reference->id)");
                $this->mysqli->query("UPDATE refs SET published=1, visibility_id=".Visibility::visible()->id." WHERE id=$reference->id");
                // TODO: find_or_create doesn't work here because of the dual primary key
                // HierarchyEntriesRef::find_or_create(array(
                //     'hierarchy_entry_id'    => $hierarchy_entry_id,
                //     'ref_id'                => $reference->id));
            }
        }
        if(isset($this->media_reference_ids[$reference_id]))
        {
            foreach($this->media_reference_ids[$reference_id] as $data_object_id => $data_object_guid)
            {
                $this->mysqli->insert("INSERT IGNORE INTO data_objects_refs (data_object_id, ref_id) VALUES ($data_object_id, $reference->id)");
                $this->mysqli->query("UPDATE refs SET published=1, visibility_id=".Visibility::visible()->id." WHERE id=$reference->id");
                // TODO: find_or_create doesn't work here because of the dual primary key - same as above with entries
            }
        }
        foreach($reference_taxon_info as $taxon_info)
        {
            $he_id = $taxon_info['hierarchy_entry_id'];
            $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs (hierarchy_entry_id, ref_id) VALUES ($he_id, $reference->id)");
            $this->mysqli->query("UPDATE refs SET published=1, visibility_id=".Visibility::visible()->id." WHERE id=$reference->id");
            // TODO: find_or_create doesn't work here because of the dual primary key - same as above with entries
        }
        $this->insert_data($row, $parameters);
    }

    public function insert_agents($row, $parameters)
    {
        self::debug_iterations("Inserting agent");
        if($this->archive_validator->has_error_by_line('http://eol.org/schema/agent/agent', $parameters['archive_table_definition']->location, $parameters['archive_line_number']))
        {
        	write_to_resource_harvesting_log("ERROR: insert_agents: has_error_by_line" . ",file_location:" . $parameters['archive_table_definition']->location . ",line_number:" . $parameters['archive_line_number']);
        	return false;
        }

        $agent_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        // we really only need to insert the agents that relate to media
        if(!isset($this->media_agent_ids[$agent_id])) return;

        $params = array("full_name"     => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_name']),
                        "given_name"    => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_firstName']),
                        "family_name"   => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_familyName']),
                        "email"         => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_mbox']),
                        "homepage"      => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_homepage']),
                        "logo_url"      => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_logo']),
                        "project"       => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_currentProject']),
                        "organization"  => @self::field_decode($row['http://eol.org/schema/agent/organization']),
                        "account_name"  => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_accountName']),
                        "openid"        => @self::field_decode($row['http://xmlns.com/foaf/spec/#term_openid']));
        // find or create this agent
        $agent = Agent::find_or_create($params);
        if(!$agent) return;
        // download the logo if there is one, and it hasn't ever been downloaded before
        if($agent->logo_url && !$agent->logo_cache_url)
        {
            if($logo_cache_url = $this->content_manager->grab_file($agent->logo_url, "partner"))
            {
                $agent->logo_cache_url = $logo_cache_url;
                $agent->save();
            }
        }

        $agent_role = AgentRole::find_or_create_by_translated_label(@self::field_decode($row['http://eol.org/schema/agent/agentRole']));
        $agent_role_id = @$agent_role->id ?: 0;
        foreach($this->media_agent_ids[$agent_id] as $data_object_id => $val)
        {
            # TODO: intelligently delete agents for objects ONLY ONCE during an import
            # TODO: figure out view order
            $this->mysqli->insert("INSERT IGNORE INTO agents_data_objects VALUES ($data_object_id, $agent->id, $agent_role_id, 0)");
        }
    }

    public function insert_gbif_references($row, $parameters)
    {
        self::debug_iterations("Inserting GBIF reference");
        $this->commit_iterations("GBIFReference", 500);
        if($this->archive_validator->has_error_by_line('http://rs.gbif.org/terms/1.0/reference', $parameters['archive_table_definition']->location, $parameters['archive_line_number']))
        {
        	write_to_resource_harvesting_log("ERROR: insert_agents: insert_gbif_references" . ",file_location:" . $parameters['archive_table_definition']->location . ",line_number:" . $parameters['archive_line_number']);
        	return false;
        }

        $reference_id = @self::field_decode($row['http://purl.org/dc/terms/identifier']);
        $taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']);
        // we really only need to insert the references that relate to taxa
        if(!isset($this->taxon_ids_inserted[$taxon_id])) return;


        $full_reference = @self::field_decode($row['http://purl.org/dc/terms/bibliographicCitation']);
        $title = @self::field_decode($row['http://purl.org/dc/terms/title']);
        $author = @self::field_decode($row['http://purl.org/dc/terms/creator']);
        $date = @self::field_decode($row['http://purl.org/dc/terms/date']);
        $description = @self::field_decode($row['http://purl.org/dc/terms/description']);
        // $subject = @self::field_decode($row['http://purl.org/dc/terms/subject']);
        $source = @self::field_decode($row['http://purl.org/dc/terms/source']);
        $language = Language::find_or_create_for_parser(@self::field_decode($row['http://purl.org/dc/terms/language']));
        $type = @self::field_decode($row['http://purl.org/dc/terms/type']);
        if($type != 'taxon') return;

        $reference_parts = array();
        if($author) $reference_parts[] = $author;
        if($date) $reference_parts[] = $date;
        if($title) $reference_parts[] = $title;
        if($source) $reference_parts[] = $source;
        if($description) $reference_parts[] = $description;
        $full_reference = implode(". ", $reference_parts);
        $full_reference = str_replace("..", ".", $full_reference);
        $full_reference = str_replace("  ", " ", $full_reference);
        if(!$full_reference) return;
        $title = null;
        $author = null;
        $date = null;
        $description = null;
        $source = null;
        $type = null;

        if($taxon_info = @$this->taxon_ids_inserted[$taxon_id])
        {
            self::uncompress_array($taxon_info);
            $params = array("provider_mangaed_id"       => $reference_id,
                            "full_reference"            => $full_reference,
                            "title"                     => $title,
                            "authors"                   => $author,
                            "publication_created_at"    => @$created ?: '0000-00-00 00:00:00',
                            "language_id"               => @$language->id ?: 0);
            $reference = Reference::find_or_create($params);

            $he_id = $taxon_info['hierarchy_entry_id'];
            $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs (hierarchy_entry_id, ref_id) VALUES ($he_id, $reference->id)");
            $this->mysqli->query("UPDATE refs SET published=1, visibility_id=".Visibility::visible()->id." WHERE id=$reference->id");
            // TODO: find_or_create doesn't work here because of the dual primary key
            // HierarchyEntriesRef::find_or_create(array(
            //     'hierarchy_entry_id'    => $hierarchy_entry_id,
            //     'ref_id'                => $reference->id));
        }
    }

    public function insert_data($row, $parameters)
    {
        $row_type = $parameters['archive_table_definition']->row_type;
        static $row_type_class_names = array(
            'http://rs.tdwg.org/dwc/terms/Taxon' => '\eol_schema\Taxon',
            'http://rs.tdwg.org/dwc/terms/Occurrence' => '\eol_schema\Occurrence',
            'http://rs.tdwg.org/dwc/terms/MeasurementOrFact' => '\eol_schema\MeasurementOrFact',
            'http://rs.tdwg.org/dwc/terms/Event' => '\eol_schema\Event',
            'http://eol.org/schema/Association' => '\eol_schema\Association',
            'http://eol.org/schema/reference/Reference' => '\eol_schema\Reference'
        );
        static $valid_measurement_of_taxon = array('1', 'yes', 'true', 'http://eol.org/schema/terms/true', 'http://eol.org/schema/terms/yes');
        if($row_class_name = @$row_type_class_names[$row_type])
        {
            $file_location = @$parameters['archive_table_definition']->location;
            $line_number = @$parameters['archive_line_number'];
            self::debug_iterations("Inserting $row_type");
            $this->commit_iterations($row_type, 500);
            # TODO: fix this with validation
            if(in_array(strtolower(@$row['http://eol.org/schema/measurementOfTaxon']), $valid_measurement_of_taxon))
            {
                $row['http://eol.org/schema/measurementOfTaxon'] = 'http://eol.org/schema/terms/true';
            }
            else $row['http://eol.org/schema/measurementOfTaxon'] = 'http://eol.org/schema/terms/false';
            if($this->archive_validator->has_error_by_line(strtolower($row_type), $file_location, $line_number))
            {
            	write_to_resource_harvesting_log("ERROR: insert_data: has_error_by_line". ",row_type:" . $row_type . ",file_location:" . $file_location . ",line_number:" . $line_number);
            	return false;
            }

            $taxon_id = FALSE;
            $occurrence_id = FALSE;
            // TODO: All of these returns should LOG SOMETHING!  :|
            if($taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonID']))
            {
                if(!isset($this->taxon_ids_inserted[$taxon_id])) return;
            }
            if($row_type != 'http://rs.tdwg.org/dwc/terms/Occurrence' &&
               $occurrence_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/occurrenceID']))
            {
                if(!isset($this->occurrence_ids_inserted[$occurrence_id])) return;
            }
            if($target_occurrence_id = @self::field_decode($row['http://eol.org/schema/targetOccurrenceID']))
            {
                if(!isset($this->occurrence_ids_inserted[$target_occurrence_id])) return;
            }

            // JRice is frustrated, wants to try another approach in parallel:
            // NOTE: this is not being inserted yet; won't work—taxon_id can
            // come through an occurrence, which is too convoluted for this
            // right now. TODO: this won't be deleted, yet...
            $entry_id = 0;
            $resource_uri = 0;
            $entry_uri = 0;
            if(empty($taxon_id) && isset($occurrence_id) &&
              array_key_exists($occurrence_id, $this->occurrence_taxon_mapping)) {
              $entry_id = $this->occurrence_taxon_mapping[$occurrence_id];
            } else if(! empty($taxon_id)) {
              $entry_id = $taxon_id;
            }
            if(! $entry_id == 0) {
              $resource_id = $this->harvest_event->resource->id;
              $resource_uri = "eol:resources/$resource_id";
              $entry_uri = "$resource_uri/entries/$taxon_id";
              if(!isset($this->entry_uris_inserted[$taxon_id])) {
                $this->entry_uris_inserted[$taxon_id] = true;
              }
            }

            list($turtle, $data_point_uri) =
              $this->prepare_turtle($row, $row_class_name, $resource_uri, $entry_uri);
            // debug("TURTLE: $turtle");

            $this->sparql_client->insert_data_in_bulk(array(
                'data' => array($turtle),
                'graph_name' => $this->harvest_event->resource->virtuoso_graph_name()));
            if($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact" || $row_type == "http://eol.org/schema/Association")
            {
            	$uri = "'" . $data_point_uri['uri'] . "'";
            	$created_at = $data_point_uri['created_at'];
            	$updated_at = $data_point_uri['updated_at'];
            	$resource_id = $data_point_uri['resource_id'];
            	$data_point_uri_type = "'" . end(split("/", $row_type)). "'";
            	$taxon_concept_id = NULL;
            	$hierarchy_entry_identifier = NULL;
                $hierarchy_entry_id = NULL;
                $source_url = NULL;
                $hierarchy_id = NULL;

            	$attributes_query  = "INSERT INTO data_point_uris(uri, created_at, updated_at, resource_id, class_type";
            	$values_query = "values($uri, $created_at, $updated_at, $resource_id, $data_point_uri_type";
                if(isset($data_point_uri['predicate']))
                {
                	 $predicate = "'" . $data_point_uri['predicate'] . "'";
                	 $attributes_query .= ",predicate";
                	 $values_query .= ", $predicate";
                	 $result = $this->mysqli->select("SELECT id FROM known_uris where uri = $predicate");
                	 if($result && $row=$result->fetch_assoc())
                	 {
                	 	$predicate_known_uri_id = $row["id"];
                	 	if($predicate_known_uri_id)
                	 	{
                	 		$attributes_query .= ",predicate_known_uri_id";
                	 		$values_query .= ", $predicate_known_uri_id";
                	 	}
                	 }
                }
                if(isset($data_point_uri['object']))
                {
                	 $attributes_query .= ",object";
                	 if($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact")
                	 {
                        $values_query .= ", '" .
                            str_replace("'", "\'", $data_point_uri['object']) .
                            "'";
                	 }
                	 else if($row_type == "http://eol.org/schema/Association") {
	                	 $hierarchy_entry_identifier = "'" . str_replace("'", "\'", $this->occurrence_taxon_mapping[$data_point_uri["object"]]) . "'";
	                	 $result = $this->mysqli->select("SELECT taxon_concept_id from hierarchy_entries where identifier =  $hierarchy_entry_identifier");
	                	 if($result && $row=$result->fetch_assoc())
	                	 {
	                	 	$target_taxon_concept_id = $row["taxon_concept_id"];
	                	 	if(!empty($target_taxon_concept_id))
		                	 {
		                	 	$values_query .= ", $target_taxon_concept_id";
		                	 }
	                	 }
                	 }
                }
                if(isset($data_point_uri['unit_of_measure']))
                {
                	 $unit_of_measure = "'" . $data_point_uri['unit_of_measure'] . "'";
                	 $attributes_query .= ",unit_of_measure";
                	 $values_query .= ", $unit_of_measure";
                	 $result = $this->mysqli->select("SELECT id FROM known_uris where uri = $unit_of_measure");
                	 if($result && $row=$result->fetch_assoc())
                	 {
                	 	$unit_of_measure_known_uri_id = $row["id"];
                	 	if($unit_of_measure_known_uri_id)
                	 	{
                	 		$attributes_query .= ",unit_of_measure_known_uri_id";
                	 		$values_query .= ", $unit_of_measure_known_uri_id";
                	 	}
                	 }
                }
            	if(isset($data_point_uri['occurrence_id']))
                {
                	 $hierarchy_entry_identifier = "'" . str_replace("'", "\'", $this->occurrence_taxon_mapping[$data_point_uri["occurrence_id"]]) . "'";
                	 $result = $this->mysqli->select("SELECT id,taxon_concept_id, source_url, hierarchy_id from hierarchy_entries where identifier =  $hierarchy_entry_identifier");
                	 if($result && $row=$result->fetch_assoc())
                	 {
                	 	$taxon_concept_id = $row["taxon_concept_id"];
                	 	$hierarchy_entry_id = $row["id"];
                	 	$source_url = $row["source_url"];
                	 	$hierarchy_id = $row["hierarchy_id"];
                	 	if(!empty($taxon_concept_id))
	                	 {
	                	 	$attributes_query .= ",taxon_concept_id";
	                	 	$values_query .= ", $taxon_concept_id";
	                	 }
                	 }

                }
                $attributes_query .= ")";
                $values_query .= ")";
            	$data_point_uri_id = $this->mysqli->insert($attributes_query . $values_query);
            	if(!empty($data_point_uri_id))
            	{
            		$source = "'" .
                  str_replace("'", "\'",
                    $this->get_hierarchy_entry_outlink($hierarchy_id,
                    $hierarchy_entry_identifier, $source_url)) .
                  "'";
            		if(isset($data_point_uri['predicate']))
            		{
                        $predicate = "'" .
                          str_replace("'", "\'", $data_point_uri['predicate']) .
                          "'";
            		}
            		else
            		{
            			$predicate = NULL;
            		}
                	$this->mysqli->insert("INSERT IGNORE INTO resource_contributions (resource_id, data_object_id, data_point_uri_id, hierarchy_entry_id, taxon_concept_id, source, object_type, identifier, predicate) VALUES ($resource_id, NULL, $data_point_uri_id, $hierarchy_entry_id, $taxon_concept_id, $source, 'data_point_uri', $hierarchy_entry_identifier, $predicate)");
            	}

            }

            if($row_type == 'http://rs.tdwg.org/dwc/terms/Occurrence' &&
               $occurrence_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/occurrenceID']))
            {
                $this->occurrence_ids_inserted[$occurrence_id] = true;
            }
        }
    }

    private function prepare_turtle($row, $row_class_name, $resource_uri, $entry_uri)
    {
        $row_type = $row_class_name::ROW_TYPE;
        $primary_key = @self::field_decode($row[$row_class_name::PRIMARY_KEY]);
        $graph_name = $this->harvest_event->resource->virtuoso_graph_name();
        // # TODO: taxon id of the partner will not be globally unique
        // # may this is a reason we will need to do represent the data in a custom way
        if($primary_key) $node_uri = $graph_name ."/". $row_class_name::GRAPH_NAME ."/". SparqlClient::to_underscore($primary_key);
        else $node_uri = $graph_name ."/". $row_class_name::GRAPH_NAME ."/". md5(serialize($row));
        $turtle = "<$node_uri> a <$row_type>";

        foreach($row as $key => $value)
        {
            $value = @self::field_decode($value);
            if($value && preg_match("/^http:\/\//", $key, $arr))
            {
                if($key == $row_class_name::PRIMARY_KEY) continue;
                if($key == "http://rs.tdwg.org/dwc/terms/taxonID")
                {
                    $turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                        SparqlClient::enclose_value($graph_name ."/taxa/". SparqlClient::to_underscore($value)) ."\n";
                    if($row_type == 'http://rs.tdwg.org/dwc/terms/Occurrence')
                    {
                    	$this->occurrence_taxon_mapping[$primary_key] = @self::field_decode($value);
                    }
                }
                elseif($key == "http://eol.org/schema/targetTaxonID")
                {
                	$turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                        SparqlClient::enclose_value($graph_name ."/taxa/". SparqlClient::to_underscore($value)) ."\n";
                    if($row_type == 'http://rs.tdwg.org/dwc/terms/targetOccurrenceID')
                    {
                    	$this->occurrence_taxon_mapping[$primary_key] = @self::field_decode($value);
                    }
                }
                elseif($key == "http://rs.tdwg.org/dwc/terms/eventID")
                {
                    $turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                        SparqlClient::enclose_value($graph_name ."/events/". SparqlClient::to_underscore($value)) ."\n";
                }elseif($key == "http://rs.tdwg.org/dwc/terms/occurrenceID" || $key == "http://eol.org/schema/targetOccurrenceID")
                {
                    $turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                        SparqlClient::enclose_value($graph_name ."/occurrences/". SparqlClient::to_underscore($value)) ."\n";
                    if($key == "http://rs.tdwg.org/dwc/terms/occurrenceID" && ($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact" || $row_type == 'http://eol.org/schema/Association'))
                    {
                        $occurrence_id = $value;
                    }
                	elseif($row_type == 'http://eol.org/schema/Association' && $key == "http://eol.org/schema/targetOccurrenceID")
                    {
                    	$object = $value;
                    }
                }elseif($key == "http://eol.org/schema/associationID")
                {
                    $turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                        SparqlClient::enclose_value($graph_name ."/associations/". SparqlClient::to_underscore($value)) ."\n";
                }elseif($key == "http://eol.org/schema/parentMeasurementID")
                {
                    $turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                        SparqlClient::enclose_value($graph_name ."/measurements/". SparqlClient::to_underscore($value)) ."\n";
                }elseif($key == "http://eol.org/schema/reference/referenceID")
                {
                    $reference_ids = self::get_foreign_keys_from_row($row, 'http://eol.org/schema/reference/referenceID');
                    foreach($reference_ids as $reference_id)
                    {
                        $turtle .= "; ". SparqlClient::enclose_value($key) ." ".
                            SparqlClient::enclose_value($graph_name ."/references/". SparqlClient::to_underscore($reference_id)) ."\n";
                    }
                }
                elseif($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact" && $key == "http://rs.tdwg.org/dwc/terms/measurementType")
                {
                	$predicate = $value;
                	$turtle .= "; ". SparqlClient::enclose_value($key) ." ". SparqlClient::enclose_value($value) ."\n";
                }
                elseif($row_type == "http://eol.org/schema/Association" && $key == "http://eol.org/schema/associationType")
                {
                	$predicate = $value;
                	$turtle .= "; ". SparqlClient::enclose_value($key) ." ". SparqlClient::enclose_value($value) ."\n";
                }
                elseif($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact" && $key == "http://rs.tdwg.org/dwc/terms/measurementValue")
                {
                	$object = $value;
                	$turtle .= "; ". SparqlClient::enclose_value($key) ." ". SparqlClient::enclose_value($value) ."\n";
                }
                elseif($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact" && $key == "http://rs.tdwg.org/dwc/terms/measurementUnit")
                {
                	$unit_of_measure = $value;
                	$turtle .= "; ". SparqlClient::enclose_value($key) ." ". SparqlClient::enclose_value($value) ."\n";
                }
                else $turtle .= "; ". SparqlClient::enclose_value($key) ." ". SparqlClient::enclose_value($value) ."\n";
            }
        }

  	    if($row_type == "http://rs.tdwg.org/dwc/terms/MeasurementOrFact" || $row_type == "http://eol.org/schema/Association")
    		{
  	    	// prepare data point uri attributes
  	    	$data_point_uri = array("uri" => $node_uri,
  							    	"created_at" => 'NOW()',
  							    	"updated_at" => 'NOW()',
  							    	"resource_id" => $this->harvest_event->resource->id);
  	    	if(isset($predicate)) { $data_point_uri["predicate"] = $predicate; }
  	    	if(isset($object)) { $data_point_uri["object"] = $object; }
  	    	if(isset($unit_of_measure)) { $data_point_uri["unit_of_measure"] = $unit_of_measure; }
  		    if(isset($occurrence_id)) { $data_point_uri["occurrence_id"] = $occurrence_id; }
    		}
    		else
  		    	$data_point_uri = NULL;
        return array($turtle, $data_point_uri);
    }

    private function commit_iterations($namespace, $iteration_size = 500)
    {
        static $iteration_counts = array();
        if(!isset($iteration_counts[$namespace])) $iteration_counts[$namespace] = 0;
        if($iteration_counts[$namespace] % $iteration_size == 0)
        {
            $this->mysqli->commit();
        }
        $iteration_counts[$namespace]++;
    }

    public static function is_this_flickr_image_in_inaturalist($row)
    {
        if(!$row['http://purl.org/dc/terms/identifier']) return false;
        if($flickr_image_identifier = $row['http://purl.org/dc/terms/identifier'])
        {
            static $flickr_identifiers_in_inaturalist = null;
            if(!$flickr_identifiers_in_inaturalist) $flickr_identifiers_in_inaturalist = self::flickr_identifiers_in_inaturalist();
            return isset($flickr_identifiers_in_inaturalist[$flickr_image_identifier]);
        }
        return false;
    }

    public static function flickr_identifiers_in_inaturalist()
    {
        static $inat_resource = null;
        if(!$inat_resource) $inat_resource = Resource::inaturalist_images();
        if(!$inat_resource) return false;

        static $last_harvest_event_id = null;
        if(!$last_harvest_event_id) $last_harvest_event_id = $inat_resource->most_recent_published_harvest_event_id();
        if(!$last_harvest_event_id) return false;

        $query = "SELECT do.object_url FROM data_objects_harvest_events dohe
            JOIN data_objects do ON (dohe.data_object_id=do.id)
            WHERE dohe.harvest_event_id=$last_harvest_event_id";
        $identifiers = array();
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row)
        {
            if(preg_match("/static\.?flickr\.com\/.+?\/([0-9]+?)_.+?\.jpg/", $row[0], $arr)) $identifiers[$arr[1]] = true;
        }
        return $identifiers;
    }

    private static function debug_iterations($message_prefix, $iteration_size = 500)
    {
        static $iteration_counts = array();
        if(!isset($iteration_counts[$message_prefix])) $iteration_counts[$message_prefix] = 0;
        if($iteration_counts[$message_prefix] % $iteration_size == 0)
        {
            if($GLOBALS['ENV_DEBUG']) echo $message_prefix ." $iteration_counts[$message_prefix]: ". memory_get_usage() .": ". time_elapsed() ."\n";
            write_to_resource_harvesting_log($message_prefix ." $iteration_counts[$message_prefix]: ". memory_get_usage() .": ". time_elapsed());
        }
        $iteration_counts[$message_prefix]++;
    }

    // this method will compare the taxonomic status of a taxon with a list of known valid statuses
    private static function check_taxon_validity(&$row)
    {
        // assume the taxon is valid by default
        $is_valid = true;
        // if the taxon has a status which isn't valid, then it isn't valid
        $taxonomic_status = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/taxonomicStatus']);
        if($taxonomic_status && trim($taxonomic_status) != '')
        {
            $is_valid = Functions::array_searchi($taxonomic_status, self::$valid_taxonomic_statuses);
            // $is_valid might be zero at this point so we need to check
            if($is_valid === null) $is_valid = false;
            else $is_valid = true;  // $is_valid could be 0 here
        }

        // if the taxon has an acceptedNameUsageID then it isn't valid
        $accepted_taxon_id = @self::field_decode($row['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']);
        if($is_valid && $accepted_taxon_id && $accepted_taxon_id != '')
        {
            $is_valid = false;
        }
        return $is_valid;
    }

    private static function append_foreign_keys_from_row(&$row, $uri, &$ids_array, &$index_key, $index_value = 1)
    {
        $ids = self::get_foreign_keys_from_row($row, $uri);
        foreach($ids as $id)
        {
            $ids_array[$id][$index_key] = $index_value;
        }
    }

    private static function get_foreign_keys_from_row(&$row, $uri)
    {
        $foreign_keys = array();
        if($field_value = @self::field_decode(@$row[$uri]))
        {
            $ids = preg_split("/[;,|]/", $field_value);
            foreach($ids as $id)
            {
                $id = trim($id);
                if(!$id) continue;
                $foreign_keys[] = $id;
            }
        }
        return $foreign_keys;
    }

    private static function append_agents(&$row, &$data_object_parameters, $uri, $agent_role)
    {
        if($field_value = @self::field_decode($row[$uri]))
        {
            $individual_agents = preg_split("/[;]/", $field_value);
            foreach($individual_agents as $agent_name)
            {
                $agent_name = trim($agent_name);
                if(!$agent_name) continue;
                $params = array("full_name" => $agent_name,
                                "agent_role" => AgentRole::find_or_create_by_translated_label($agent_role));
                $data_object_parameters["agents"][] = $params;
            }
        }
    }

    private function collect_dataset_attribution()
    {
        $this->dataset_metadata = array();
        if(is_dir($this->harvest_event->resource->archive_path() . "dataset") && file_exists($this->harvest_event->resource->archive_path() . "dataset/col.xml"))
        {
            foreach(glob($this->harvest_event->resource->archive_path() . "dataset/*") as $filename)
            {
                if(preg_match("/\/([0-9]+)\.xml$/", $filename, $arr)) $dataset_id = $arr[1];
                $xml = simplexml_load_file($filename);
                $title = trim($xml->dataset->title);
                if(preg_match("/^(.*) in the Catalogue of Life/", $title, $arr)) $title = trim($arr[1]);
                $title = str_replace("  ", " ", $title);

                $editors = trim($xml->additionalMetadata->metadata->sourceDatabase->authorsAndEditors);
                if(preg_match("/^(.*)\. For a full list/", $editors, $arr)) $editors = trim($arr[1]);
                if(preg_match("/^(.*); for detailed information/", $editors, $arr)) $editors = trim($arr[1]);
                $editors = str_replace("  ", " ", $editors);

                $abbreviatedName = trim($xml->additionalMetadata->metadata->sourceDatabase->abbreviatedName);

                $this->dataset_metadata[$abbreviatedName]['title'] = $title;
                $this->dataset_metadata[$abbreviatedName]['editors'] = $editors;
                $this->dataset_metadata[$abbreviatedName]['abbreviatedName'] = $abbreviatedName;
                $this->dataset_metadata[$abbreviatedName]['datasetID'] = $dataset_id;
                $this->dataset_metadata[$dataset_id] =& $this->dataset_metadata[$abbreviatedName];
            }

            // now go grab the citation information from the COL website
            $url = "http://www.catalogueoflife.org/col/info/cite";
            $options_for_log_harvest = array('resource_id' => $this->harvest_event->resource->id);
            $html = Functions::get_remote_file($url, $options_for_log_harvest);
            preg_match_all("/<p><strong>(.*?)<\/strong><br\/>(.*?)<\/p>/ims", $html, $matches, PREG_SET_ORDER);
            foreach($matches as $match)
            {
                $dataset_name = $match[1];
                if(preg_match("/^(.*) via ITIS/", $dataset_name, $arr)) $dataset_name = trim($arr[1]);

                $citation = $match[2];

                if(isset($this->dataset_metadata[$dataset_name]))
                {
                    $this->dataset_metadata[$dataset_name]['citation'] = $citation;
                }elseif($dataset_name == "Species 2000 Common Names" && isset($this->dataset_metadata["Catalogue of Life"]))
                {
                    $this->dataset_metadata["Catalogue of Life"]['citation'] = $citation;
                }
            }
            if(!isset($this->dataset_metadata["Catalogue of Life"]['citation']) || !isset($this->dataset_metadata["FishBase"]['citation']))
            {
                echo "Tried getting attribution for Catalogue of Life datasets, but there was a problem\n";
                write_to_resource_harvesting_log("Tried getting attribution for Catalogue of Life datasets, but there was a problem");
                exit;
            }
        }
    }

    private static function field_decode($string)
    {
        return trim($string);
    }

    private static function compress_array(&$array)
    {
        $array = gzcompress(serialize($array), 3);
    }

    private static function uncompress_array(&$array)
    {
        $array = unserialize(gzuncompress($array));
    }
}

?>
