<?php
namespace php_active_record;

class Resource extends ActiveRecord
{
    public static $belongs_to = array(
            array('content_partner'),
            array('service_type'),
            array('resource_status'),
            array('collection'),
            array('preview_collection', 'class_name' => 'Collection', 'foreign_key' => 'preview_collection_id'),
            array('license'),
            array('hierarchy'),
            array('language')
        );
        
    public $harvest_event;
    public $last_harvest_event;
    public $start_harvest_time;
    public $end_harvest_time;
    
    public static function delete($id)
    {
        if(!$id) return false;
        $resource = Resource::find($id);
        if(!$resource->id) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        
        //$mysqli->delete("DELETE do FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE ado FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN agents_data_objects ado ON (dohe.data_object_id=ado.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dohent FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_hierarchy_entries dohent ON (dohe.data_object_id=dohent.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dor FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_refs dor ON (dohe.data_object_id=dor.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE ado FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN audiences_data_objects ado ON (dohe.data_object_id=ado.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE doii FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_info_items doii ON (dohe.data_object_id=doii.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dotoc FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_table_of_contents dotoc ON (dohe.data_object_id=dotoc.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dohe FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) WHERE he.resource_id=$id");
        
        // primary hierarchy
        if($resource->hierarchy_id)
        {
            $mysqli->delete("DELETE her FROM hierarchy_entries he JOIN hierarchy_entries_refs her ON (he.id=her.hierarchy_entry_id) WHERE he.hierarchy_id=$resource->hierarchy_id");
            $mysqli->delete("DELETE s FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.hierarchy_id=$resource->hierarchy_id AND s.hierarchy_id=$resource->hierarchy_id");
            $mysqli->delete("DELETE he FROM hierarchy_entries he WHERE he.hierarchy_id=$resource->hierarchy_id");
            $mysqli->delete("DELETE hehe FROM harvest_events he STRAIGHT_JOIN harvest_events_hierarchy_entries hehe ON (he.id=hehe.harvest_event_id) WHERE he.resource_id=$id");
        }
        
        // DWC hierarchy
        if($resource->dwc_hierarchy_id)
        {
            $mysqli->delete("DELETE her FROM hierarchy_entries he JOIN hierarchy_entries_refs her ON (he.id=her.hierarchy_entry_id) WHERE he.hierarchy_id=$resource->dwc_hierarchy_id");
            $mysqli->delete("DELETE s FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.hierarchy_id=$resource->hierarchy_id AND s.hierarchy_id=$resource->dwc_hierarchy_id");
            $mysqli->delete("DELETE he FROM hierarchy_entries he WHERE he.hierarchy_id=$resource->dwc_hierarchy_id");
        }
        
        $mysqli->delete("DELETE FROM harvest_events WHERE resource_id=$id");
        $mysqli->delete("DELETE FROM resources WHERE id=$id");
        
        $mysqli->end_transaction();
    }
    
    public function resource_path()
    {
        return CONTENT_RESOURCE_LOCAL_PATH.$this->id.".xml";
    }
    
    public function archive_path()
    {
        return CONTENT_RESOURCE_LOCAL_PATH . $this->id ."/";
    }
    
    public function resource_deletions_path()
    {
        return CONTENT_RESOURCE_LOCAL_PATH.$this->id."_delete.xml";
    }
    
    public function virtuoso_graph_name()
    {
        return  "http://eol.org/resources/". $this->id;
    }

    public function is_translation_resource()
    {
        // if($this->id == 6) return true;
        if($this->id == 296) return true;
        return false;
    }
    
    public function is_archive_resource()
    {
        if(!is_dir($this->archive_path())) return false;
        if(!file_exists($this->archive_path() . "/meta.xml")) return false;
        return true;
    }
    
    public static function wikipedia()
    {
        return self::find_by_title('Wikipedia');
    }
    
    public static function flickr()
    {
        return self::find_by_title('EOL Group on Flickr');
    }
    
    public static function inaturalist_images()
    {
        return self::find_by_title('iNaturalist Images');
    }
    
    public static function ligercat()
    {
        return self::find_by_title('LigerCat');
    }

    public function is_eol_flickr_group()
    {
        if(self::flickr() && $this->id == self::flickr()->id) return true;
        return false;
    }

    public function resource_file_path()
    {
        return CONTENT_RESOURCE_LOCAL_PATH . $this->id . ".xml";
    }
    
    public function ready_to_update()
    {
        //the resource hasn't been downloaded yet
        if(!file_exists($this->resource_file_path())) return true;
        
        return $this->ready_to_harvest(12);
        
        ////Adding 12 hours to last modified to offset time it takes to update some resources
        //$last_updated = Functions::file_hours_since_modified($this->resource_file_path()) + 12;
        //if($last_updated < $this->refresh_period_hours) return false;
        
        return true;
    }
    
    public function set_autopublish($value)
    {
        $auto_publish = ($value === true) ? 1 : 0;
        $this->mysqli->update("UPDATE resources SET auto_publish=$auto_publish WHERE id=$this->id");
        $this->auto_publish = $auto_publish;
    }
    
    public function set_accesspoint_url($accesspoint_url)
    {
        $this->mysqli->update("UPDATE resources SET accesspoint_url='".$this->mysqli->escape($accesspoint_url)."' WHERE id=$this->id");
        $this->accesspoint_url = $accesspoint_url;
    }
    
    // will return boolean of THIS resource is ready
    public function ready_to_harvest($hours_ahead_of_time = null)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $extra_hours_clause = "";
        if($hours_ahead_of_time) $extra_hours_clause = " - $hours_ahead_of_time";
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM resources WHERE id=$this->id AND (resource_status_id=".ResourceStatus::force_harvest()->id." OR (harvested_at IS NULL AND (resource_status_id=".ResourceStatus::validated()->id." OR resource_status_id=".ResourceStatus::validation_failed()->id." OR resource_status_id=".ResourceStatus::processing_failed()->id." OR resource_status_id=".ResourceStatus::upload_failed()->id.")) OR (refresh_period_hours!=0 AND DATE_ADD(harvested_at, INTERVAL (refresh_period_hours $extra_hours_clause) HOUR)<=NOW() AND resource_status_id IN (".ResourceStatus::validated()->id.", ".ResourceStatus::validation_failed()->id.", ".ResourceStatus::upload_failed()->id.", ".ResourceStatus::processed()->id.", ".ResourceStatus::processing_failed()->id.", ".ResourceStatus::published()->id.")))");
        
        if($result && $row=$result->fetch_assoc()) return true;
        return false;
    }
    
    // static method to find ALL resources ready
    public static function ready_for_harvesting($hours_ahead_of_time = null)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $resources = array();
        $extra_hours_clause = "";
        if($hours_ahead_of_time) $extra_hours_clause = " - $hours_ahead_of_time";
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM resources WHERE resource_status_id=".ResourceStatus::force_harvest()->id." OR (harvested_at IS NULL AND (resource_status_id=".ResourceStatus::validated()->id." OR resource_status_id=".ResourceStatus::validation_failed()->id." OR resource_status_id=".ResourceStatus::processing_failed()->id.")) OR (refresh_period_hours!=0 AND DATE_ADD(harvested_at, INTERVAL (refresh_period_hours $extra_hours_clause) HOUR)<=NOW() AND resource_status_id IN (".ResourceStatus::upload_failed()->id.", ".ResourceStatus::validated()->id.", ".ResourceStatus::validation_failed()->id.", ". ResourceStatus::processed()->id .", ".ResourceStatus::processing_failed()->id.", ".ResourceStatus::published()->id.")) ORDER BY position ASC");		
        while($result && $row=$result->fetch_assoc())
        {
            $resources[] = $resource = Resource::find($row["id"]);
        }
	 
        return $resources;
    }
   
    public static function ready_for_publishing()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        $resources = array();
        $result = $mysqli->query("SELECT SQL_NO_CACHE DISTINCT resource_id FROM harvest_events WHERE publish=1 AND published_at IS NULL");
        while($result && $row=$result->fetch_assoc())
        {
            $resources[] = $resource = Resource::find($row["resource_id"]);
        }
        return $resources;
    }
    
    public function last_harvest_event_id()
    {
        if(isset($this->last_harvest_event_id)) return $this->last_harvest_event_id;
        
        $this->last_harvest_event_id = 0;
        if($this->harvest_event) $result = $this->mysqli->query("SELECT MAX(id) as id FROM harvest_events WHERE resource_id=$this->id AND id<".$this->harvest_event->id);
        else $result = $this->mysqli->query("SELECT SQL_NO_CACHE MAX(id) as id FROM harvest_events WHERE resource_id=$this->id");
        if($result && $row=$result->fetch_assoc())
        {
            $this->last_harvest_event_id = $row["id"];
        }
        
        return $this->last_harvest_event_id;
    }
    
    public function most_recent_harvest_event_id()
    {
        $result = $this->mysqli->query("SELECT SQL_NO_CACHE MAX(id) as id FROM harvest_events WHERE resource_id=$this->id");
        if($result && $row=$result->fetch_assoc())
        {
            return $row["id"];
        }
        return 0;
    }
    
    public function most_recent_published_harvest_event_id()
    {
        $result = $this->mysqli->query("SELECT SQL_NO_CACHE MAX(id) as id FROM harvest_events WHERE resource_id=$this->id AND published_at IS NOT NULL");
        if($result && $row=$result->fetch_assoc())
        {
            return $row["id"];
        }
        return 0;
    }
    
    
    public function data_supplier()
    {
        if(@$this->data_supplier) return $this->data_supplier;
        $this->data_supplier = $this->content_partner->user->agent;
        return $this->data_supplier;
    }
    
    public function unpublish_data_objects()
    {
        if($last_id = $this->most_recent_published_harvest_event_id())
        {
            $this->mysqli->update_where("data_objects", "id", "SELECT do.id FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE harvest_event_id=$last_id AND do.published=1", "published=0", 2000000, 2500);
        }
    }
    
    public function unpublish_hierarchy_entries()
    {
        $this->mysqli->update_where("hierarchy_entries", "id", "SELECT id FROM hierarchy_entries WHERE hierarchy_id=$this->hierarchy_id AND published=1", "published=0, visibility_id=".Visibility::invisible()->id);
        $this->mysqli->update_where("synonyms", "id", "SELECT s.id FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id AND he.hierarchy_id=s.hierarchy_id) WHERE he.hierarchy_id=$this->hierarchy_id AND s.published=1", "published=0");
    }
    
    public function force_publish()
    {
        if($harvest_event_id = $this->most_recent_harvest_event_id())
        {
            $harvest_event = HarvestEvent::find($harvest_event_id);
            if(!$harvest_event->published_at && $harvest_event->completed_at)
            {
                $harvest_event->publish = 1;
                $harvest_event->save();
            }
        }
    }
    
    public function publish($fast_for_testing = false)
    {
        $this->mysqli->begin_transaction();
        if($harvest_event_id = $this->most_recent_harvest_event_id())
        {
            $harvest_event = HarvestEvent::find($harvest_event_id);
            if(!$harvest_event->published_at && $harvest_event->completed_at && $harvest_event->publish)
            {
                // make all objects in last harvest visible if they were in preview mode
                $harvest_event->make_objects_visible();
                
                // preserve visibilities from older versions of same objects
                // if the older versions were curated they may be invible or inappropriate and we don't want to lose that info
                if($last_id = $this->most_recent_published_harvest_event_id())
                {
                    $harvest_event->inherit_visibilities_from($last_id);
                    $this->mysqli->commit();
                }
                
                // set published=0 for ALL objects associated with this resource
                $this->unpublish_data_objects();
                
                // now only set published=1 for the objects in the latest harvest
                $harvest_event->publish_objects();
                $this->mysqli->commit();
                
                // set the harvest published_at date
                $harvest_event->published();
                $this->mysqli->commit();
                
                if($this->hierarchy_id)
                {
                    // unpublish all concepts associated with this resource
                    $this->unpublish_hierarchy_entries();
                    $this->mysqli->commit();
                    
                    // now set published=1 for all concepts in the latest harvest
                    $harvest_event->publish_hierarchy_entries();
                    $this->mysqli->commit();
                    
                    // make sure all concepts are published
                    Hierarchy::fix_published_flags_for_taxon_concepts();
                    $this->mysqli->commit();
                    
                    if(!$fast_for_testing)
                    {
                        // Rebuild the Solr index for this hierarchy
                        $indexer = new HierarchyEntryIndexer();
                        $indexer->index($this->hierarchy_id);
                        
                        // Compare this hierarchy to all others and store the results in the hierarchy_entry_relationships table
                        $harvest_event->compare_new_hierarchy_entries();
                        $harvest_event->create_taxon_relations_graph();
                    }
                }
                
                $this->update_names();
                $this->mysqli->commit();
                
                $this->mysqli->commit();
                $harvest_event->resource->refresh();
                if(!$fast_for_testing)
                {
                    $harvest_event->create_collection();
                    $harvest_event->index_for_search();
                }
                $this->mysqli->update("UPDATE resources SET resource_status_id=". ResourceStatus::published()->id ." WHERE id=$this->id");
            }
        }
        $this->mysqli->end_transaction();
    }
    
    public function harvest($validate = true, $validate_only_welformed = false, $fast_for_testing = false)
    {
        debug("Starting harvest of resource: $this->id");
        
        debug("Validating resource: $this->id");
        $valid = $validate ? $this->validate() : true;
        debug("Validated resource: $this->id");
        if($valid)
        {
            $this->mysqli->begin_transaction();
            $this->insert_hierarchy();
            $sparql_client = SparqlClient::connection();
            $sparql_client->delete_graph($this->virtuoso_graph_name());
            $this->start_harvest();
            
            debug("Parsing resource: $this->id");
            if($this->is_translation_resource())
            {
                require_library('TranslationSchemaParser');
                TranslationSchemaParser::parse($this->resource_path(), 'php_active_record\\SchemaConnection::add_translated_taxon', false, $this);
            }elseif($this->is_archive_resource())
            {
                $this->create_archive_validator();
                $ingester = new ArchiveDataIngester($this->harvest_event);
                $ingester->parse(false, $this->archive_reader, $this->archive_validator);
                unset($ingester);
            }else
            {
                $connection = new SchemaConnection($this);
                SchemaParser::parse($this->resource_path(), $connection, false);
                unset($connection);
            }
            debug("Parsed resource: $this->id");
            $this->mysqli->commit();
            
            // if the resource only contains information to update, then check for a 
            // _delete file for the identifiers of the objects to delete
            $this->add_unchanged_data_to_harvest();
            
            $this->end_harvest();
            $this->mysqli->commit();
            
            // if there are things in preview mode in old harvest which are not in this harvest
            // then set them to be invisible
            $this->make_old_preview_objects_invisible();
            $this->mysqli->commit();
            
            // do the same thing with hierarchy entries
            $this->make_old_preview_entries_invisible();
            $this->mysqli->commit();
            
            if($this->hierarchy_id && !$this->is_translation_resource())
            {
                $hierarchy = Hierarchy::find($this->hierarchy_id);
                debug("Assigning nested set values resource: $this->id");
                Tasks::rebuild_nested_set($this->hierarchy_id);
                debug("Finished assigning: $this->id");
                $this->make_new_hierarchy_entries_preview($hierarchy);
                
                if(!$this->auto_publish)
                {
                    // Rebuild the Solr index for this hierarchy
                    $indexer = new HierarchyEntryIndexer();
                    $indexer->index($this->hierarchy_id);
                    
                    $this->harvest_event->compare_new_hierarchy_entries();
                    
                    $this->harvest_event->create_collection();
                }
                
                if($this->vetted)
                {
                    // Vet all taxon concepts associated with this resource
                    $this->mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.vetted_id=" . Vetted::trusted()->id . " WHERE he.hierarchy_id=$this->hierarchy_id");
                    $this->mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.vetted_id=" . Vetted::trusted()->id . " WHERE he.hierarchy_id=$this->hierarchy_id AND he.vetted_id!=" . Vetted::untrusted()->id);
                    $this->mysqli->update("UPDATE hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id AND he.hierarchy_id=s.hierarchy_id) SET s.vetted_id=" . Vetted::trusted()->id . " WHERE he.hierarchy_id=$this->hierarchy_id and s.vetted_id = 0");
                }
                
                // after all the resource hierarchy stuff has been taken care of - import the DWC Archive into a
                // SEPARATE hierarchy, but try to get the same concept IDs
                // $this->import_dwc_archive();
            }
            
            if($this->vetted && $this->harvest_event)
            {
                // set vetted=trusted for all objects in this harvest
                $this->harvest_event->vet_objects();
            }
            
            $this->mysqli->end_transaction();
            
            if($this->auto_publish)
            {
                $this->harvest_event->publish = 1;
                $this->harvest_event->save();
                $this->publish($fast_for_testing);
            }
            
            if($GLOBALS['ENV_NAME'] == 'production')
            {
                $this->harvest_event->refresh();
                $this->harvest_event->send_emails_about_outlier_harvests();
            }
        }
        $this->harvest_event = null;
    }
    
    public function add_unchanged_data_to_harvest()
    {
        // there is no _delete file so we assume the resource is complete
        if(!file_exists($this->resource_deletions_path())) return false;
        
        if($this->harvest_event)
        {
            $last_harvest_event_id = $this->last_harvest_event_id();
            // if there isn't a previous harvest there's nothing to delete or remain unchanged
            if(!$last_harvest_event_id) return false;
            
            $identifiers_to_delete = array();
            $file = file($this->resource_deletions_path());
            foreach($file as $line)
            {
                $id = trim($line);
                if($id) $identifiers_to_delete[] = trim($line);
            }
            
            // at this point identifiers_to_delete could be empty - meaning we don't want to delete anything and
            // we want to bring over all old items not referenced in the resource file
            $identifiers_to_delete_string = "'". implode("','", $identifiers_to_delete) ."'";
            if($identifiers_to_delete_string == "''") $identifiers_to_delete_string = "'NONSENSE 9832rhjgovih'";
            
            $unchanged_status_id = Status::unchanged()->id;
            // add the unchanged data objects
            $outfile = $this->mysqli->select_into_outfile("SELECT ".$this->harvest_event->id.", dohe.data_object_id, dohe.guid, $unchanged_status_id FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) LEFT JOIN data_objects_harvest_events dohe_current ON (dohe_current.harvest_event_id=".$this->harvest_event->id." AND dohe_current.guid=dohe.guid) WHERE do.identifier NOT IN ($identifiers_to_delete_string) AND dohe.harvest_event_id=$last_harvest_event_id AND dohe_current.data_object_id IS NULL");
            $GLOBALS['db_connection']->load_data_infile($outfile, 'data_objects_harvest_events');
            unlink($outfile);
            
            
            // add the unchanged taxa
            $outfile = $this->mysqli->select_into_outfile("SELECT ".$this->harvest_event->id.", hehe.hierarchy_entry_id, hehe.guid, $unchanged_status_id FROM data_objects_harvest_events dohe JOIN data_objects_hierarchy_entries dohent ON (dohe.data_object_id=dohent.data_object_id) JOIN harvest_events_hierarchy_entries hehe ON (dohent.hierarchy_entry_id=hehe.hierarchy_entry_id) JOIN data_objects do ON (dohe.data_object_id=do.id) LEFT JOIN harvest_events_hierarchy_entries hehe_current ON (hehe_current.harvest_event_id=".$this->harvest_event->id." AND hehe_current.guid=hehe.guid) WHERE do.identifier NOT IN ($identifiers_to_delete_string) AND dohe.harvest_event_id=$last_harvest_event_id AND hehe.harvest_event_id=$last_harvest_event_id AND hehe_current.hierarchy_entry_id IS NULL");
            $GLOBALS['db_connection']->load_data_infile($outfile, 'harvest_events_hierarchy_entries');
            unlink($outfile);
            
            // at this point everything has been added EXCEPT the things we want to delete
        }
    }
    
    
    public function update_names()
    {
        if($this->harvest_event)
        {
            $taxon_concept_ids = array();
            $query = "SELECT DISTINCT he.taxon_concept_id FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id=". $this->harvest_event->id;
            foreach($this->mysqli->iterate_file($query) as $row_number => $row)
            {
                $id = $row[0];
                $taxon_concept_ids[$id] = $id;
            }
            if($taxon_concept_ids)
            {
                Tasks::update_taxon_concept_names($taxon_concept_ids);
            }
        }
    }
    
    public function update_taxon_concepts_solr_index()
    {
        if($this->harvest_event)
        {
            $taxon_concept_ids = array();
            $query = "SELECT DISTINCT he.taxon_concept_id FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id=". $this->harvest_event->id;
            foreach($this->mysqli->iterate_file($query) as $row_number => $row)
            {
                $id = $row[0];
                $taxon_concept_ids[$id] = $id;
            }
            
            if($last_id = $this->last_harvest_event_id())
            {
                $query = "SELECT DISTINCT he.taxon_concept_id FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id=". $last_id;
                foreach($this->mysqli->iterate_file($query) as $row_number => $row)
                {
                    $id = $row[0];
                    $taxon_concept_ids[$id] = $id;
                }
            }
            
            // print_r($taxon_concept_ids);
            echo count($taxon_concept_ids);
            echo " $last_id\n";
            $indexer = new TaxonConceptIndexer();
            $indexer->index_concepts($taxon_concept_ids);
        }
    }
    
    public function update_data_objects_solr_index()
    {
        if($this->harvest_event)
        {
            $data_object_ids = array();
            $query = "SELECT DISTINCT data_object_id FROM data_objects_harvest_events dohe WHERE dohe.harvest_event_id=". $this->harvest_event->id;
            foreach($this->mysqli->iterate_file($query) as $row_number => $row)
            {
                $id = $row[0];
                $data_object_ids[$id] = $id;
            }
            
            if($last_id = $this->last_harvest_event_id())
            {
                $query = "SELECT DISTINCT data_object_id FROM data_objects_harvest_events dohe WHERE dohe.harvest_event_id=". $last_id;
                foreach($this->mysqli->iterate_file($query) as $row_number => $row)
                {
                    $id = $row[0];
                    $data_object_ids[$id] = $id;
                }
            }
            
            // print_r($data_object_ids);
            echo count($data_object_ids);
            echo " $last_id\n";
            $indexer = new DataObjectAncestriesIndexer();
            $indexer->index_data_objects($data_object_ids);
        }
    }
    
    public function make_new_hierarchy_entries_preview($hierarchy)
    {
        if($this->harvest_event)
        {
            $this->mysqli->update("UPDATE harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) SET he.visibility_id=". Visibility::preview()->id ." WHERE hehe.harvest_event_id=".$this->harvest_event->id." AND he.visibility_id=". Visibility::invisible()->id);
            $this->make_new_hierarchy_entries_parents_preview($hierarchy);
        }
    }
    
    public function make_new_hierarchy_entries_parents_preview($hierarchy)
    {
        $result = $this->mysqli->query("SELECT 1 FROM hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) WHERE he.hierarchy_id=$hierarchy->id AND he.visibility_id=". Visibility::preview()->id ." AND he_parents.visibility_id=". Visibility::invisible()->id ." LIMIT 1");
        while($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) SET he_parents.visibility_id=". Visibility::preview()->id ." WHERE he.hierarchy_id=$hierarchy->id AND he.visibility_id=". Visibility::preview()->id ." AND he_parents.visibility_id=". Visibility::invisible()->id);
            
            $result = $this->mysqli->query("SELECT 1 FROM hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) WHERE he.hierarchy_id=$hierarchy->id AND he.visibility_id=". Visibility::preview()->id ." AND he_parents.visibility_id=". Visibility::invisible()->id ." LIMIT 1");
        }
    }
    
    // this method will make sure that any data objects that were in the previous harvest event, which are not
    // in this current harvest event, whose visibility id is Preview, are set to be invisible
    public function make_old_preview_objects_invisible()
    {
        if($this->harvest_event && $last_harvest_event_id = $this->last_harvest_event_id())
        {
            $result = $this->mysqli->query("
                SELECT dohe1.data_object_id
                FROM
                    (data_objects_harvest_events dohe1
                    JOIN data_objects_hierarchy_entries dohent ON (dohe1.data_object_id=dohent.data_object_id))
                LEFT JOIN data_objects_harvest_events dohe2 ON (dohe1.data_object_id=dohe2.data_object_id AND dohe2.harvest_event_id=".$this->harvest_event->id.")
                WHERE dohe1.harvest_event_id=$last_harvest_event_id
                AND dohent.visibility_id=". Visibility::preview()->id ."
                AND dohe2.data_object_id IS NULL");
            while($result && $row=$result->fetch_assoc())
            {
                $this->mysqli->update("UPDATE data_objects_hierarchy_entries SET visibility_id=". Visibility::invisible()->id . " WHERE data_object_id=". $row['data_object_id']);
            }
        }
    }
    
    public function make_old_preview_entries_invisible()
    {
        if($this->harvest_event && $last_harvest_event_id = $this->last_harvest_event_id())
        {
            $result = $this->mysqli->query("
                SELECT hehe1.hierarchy_entry_id
                FROM
                    (harvest_events_hierarchy_entries hehe1
                    JOIN hierarchy_entries he1 ON (hehe1.hierarchy_entry_id=he1.id))
                LEFT JOIN harvest_events_hierarchy_entries hehe2 ON (hehe1.hierarchy_entry_id=hehe2.hierarchy_entry_id AND hehe2.harvest_event_id=".$this->harvest_event->id.")
                WHERE hehe1.harvest_event_id=$last_harvest_event_id
                AND he1.visibility_id=". Visibility::preview()->id ."
                AND hehe2.hierarchy_entry_id IS NULL");
            while($result && $row=$result->fetch_assoc())
            {
                $this->mysqli->update("UPDATE hierarchy_entries SET visibility_id=". Visibility::invisible()->id . " WHERE id=". $row['hierarchy_entry_id']);
            }
        }
    }
    
    
    public function start_harvest()
    {
        if(!$this->harvest_event)
        {
            // Set resource as 'Being Processed'
            $this->mysqli->update("UPDATE resources SET resource_status_id=". ResourceStatus::being_processed()->id ." WHERE id=$this->id");
            
            // Create this harvest event
            $this->harvest_event = HarvestEvent::create(array('resource_id' => $this->id));
            $this->harvest_event->resource = $this;
            $this->start_harvest_time  = date('Y m d H');
        }
    }
    
    public function end_harvest()
    {
        if($this->harvest_event)
        {
            $this->harvest_event->completed();
            $this->mysqli->update("UPDATE resources SET resource_status_id=". ResourceStatus::processed()->id .", harvested_at=NOW(), notes='' WHERE id=$this->id");
            $this->end_harvest_time  = date('Y m d H');
            $this->harvest_event->resource->refresh();
        }
    }
    
    public function create_archive_validator()
    {
        if(isset($this->archive_validator)) return $this->archive_validator;
        $this->archive_reader = new ContentArchiveReader(null, $this->archive_path());
        $this->archive_validator = new ContentArchiveValidator($this->archive_reader, $this);
    }
    
    public function validate()
    {
        $valid = false;
        $error_string = null;
        if($this->is_archive_resource())
        {
            $this->create_archive_validator();
            if($this->archive_validator->is_valid(true)) $valid = true;  // valid
            $errors = array_merge($this->archive_validator->structural_errors(), $this->archive_validator->display_errors());
            if($errors)
            {
                $errors_as_string = array();
                foreach($errors as $error)
                {
                    $errors_as_string[] = $error->__toString();
                }
                $error_string = $this->mysqli->escape(implode("<br>", $errors_as_string));
            }
        }else
        {
            $validation_result = SchemaValidator::validate($this->resource_path());
            if($validation_result===true) $valid = true;  // valid
            else $error_string = $this->mysqli->escape(implode("<br>", $validation_result));
        }
        if($error_string)
        {
            if(strlen($error_string) > 50000) $error_string = substr($error_string, 0, 50000) . "...";
            $this->mysqli->update("UPDATE resources SET notes='$error_string' WHERE id=$this->id");
        }
        if(!$valid)
        {
            $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::processing_failed()->id." WHERE id=$this->id");
        }
        return $valid;
    }
    
    public function insert_hierarchy()
    {
        if($this->hierarchy_id) return $this->hierarchy_id;
        
        $provider_agent = $this->data_supplier();
        
        $params = array();
        if(@$provider_agent->id) $params["agent_id"] = $provider_agent->id;
        $params["label"] = $this->title;
        $params["description"] = "From resource $this->title ($this->id)";
        $params["complete"] = 0;
        $hierarchy = Hierarchy::find_or_create($params);
        
        $this->mysqli->insert("UPDATE resources SET hierarchy_id=$hierarchy->id WHERE id=$this->id");
        # TODO - get real object updating in place to take care of value updates
        $this->hierarchy_id = $hierarchy->id;
        
        return $hierarchy->id;
    }
    
    private function insert_dwc_hierarchy()
    {
        // if there is already an archive hierarchy - make a copy for the new data
        if($this->dwc_hierarchy_id)
        {
            return $this->mysqli->query("INSERT INTO hierarchies (agent_id, label, description, browsable, complete) SELECT agent_id, label, description, browsable, complete FROM hierarchies WHERE id=$this->dwc_hierarchy_id");
        }
        
        $provider_agent = $this->data_supplier();
        $params = array();
        if(@$provider_agent->id) $params["agent_id"] = $provider_agent->id;
        $params["label"] = $this->title;
        $params["description"] = "From resource $this->title dwc archive";
        $hierarchy = Hierarchy::find_or_create($params);
        
        $this->mysqli->insert("UPDATE resources SET dwc_hierarchy_id=$hierarchy->id WHERE id=$this->id");
        # TODO - get real object updating in place to take care of value updates
        $this->dwc_hierarchy_id = $hierarchy->id;
        
        return $hierarchy->id;
    }
    
    private function import_dwc_archive()
    {
        if(!$this->dwc_archive_url) return false;
        try
        {
            $archive_hierarchy_id = $this->insert_dwc_hierarchy();
            
            $dwca = new DarwinCoreArchiveHarvester($this->dwc_archive_url);
            $taxa = $dwca->get_core_taxa();
            $vernaculars = $dwca->get_vernaculars();
            $taxa = array_merge($taxa, $vernaculars);
            
            $vetted_id = $this->vetted ? Vetted::trusted()->id : Vetted::unknown()->id;
            $archive_hierarchy = Hierarchy::find($archive_hierarchy_id);
            $importer = new TaxonImporter($archive_hierarchy, $vetted_id, Visibility::visible()->id, 1);
            $importer->import_taxa($taxa);
            
            $taxon_concept_ids = array();
            $query = "SELECT taxon_concept_id FROM hierarchy_entries WHERE hierarchy_id=$archive_hierarchy_id";
            foreach($this->mysqli->iterate_file($query) as $row_number => $row)
            {
                $id = $row[0];
                $taxon_concept_ids[$id] = $id;
            }
            if($taxon_concept_ids)
            {
                Tasks::update_taxon_concept_names($taxon_concept_ids);
            }
            
            // Rebuild the Solr index for this hierarchy
            $indexer = new HierarchyEntryIndexer();
            $indexer->index($archive_hierarchy_id);
            
            // Compare this hierarchy to all others and store the results in the hierarchy_entry_relationships table
            $relator = new RelateHierarchies(array('hierarchy_to_compare' => $archive_hierarchy));
            $relator->process_hierarchy();
            
            // Use the entry relationships to assign the proper concept IDs
            CompareHierarchies::begin_concept_assignment($archive_hierarchy_id, true);
            
            // this means the resource already had a hierarchy - and we just inserted one to take its place, so
            // we now need to update resources to point to the new one now that its ready
            if($archive_hierarchy_id != $this->dwc_hierarchy_id)
            {
                $this->mysqli->update("UPDATE resources SET dwc_hierarchy_id=$archive_hierarchy_id WHERE id=$this->id");
                Hierarchy::delete($this->dwc_hierarchy_id);
                $this->dwc_hierarchy_id = $archive_hierarchy_id;
            }
        }catch(Exception $e)
        {
            return false;
        }
    }
    
    // private function add_orphaned_entries()
    // {
    //     // ADD TO TAXA
    //     $tmp_file_path = temp_filepath();
    //     $OUT = fopen($tmp_file_path, 'w+');
    //     $result = $this->mysqli->query("SELECT he.id, he.name_id, n.string FROM (hierarchy_entries he JOIN names n ON (he.name_id=n.id)) LEFT JOIN taxa t ON (he.id=t.hierarchy_entry_id) WHERE he.hierarchy_id=$this->hierarchy_id");
    //     while($result && $row=$result->fetch_assoc())
    //     {
    //         $guid = Functions::generate_guid();
    //         $string = $this->mysqli->escape($row['string']);
    //         $name_id = $row['name_id'];
    //         $he_id = $row['id'];
    //         fwrite($OUT, "NULL\t$guid\tNULL\tNULL\tNULL\tNULL\tNULL\t$string\t$name_id\t$he_id\tNULL\tNULL\n");
    //     }
    //     fclose($OUT);
    //     $this->mysqli->load_data_infile($tmp_file_path, 'taxa', 'IGNORE', '', 6000000);
    //     @unlink($tmp_file_path);
    //     
    //     // ADD TO RESOURCES TAXA
    //     $rt_path = temp_filepath();
    //     $RESOURCES_TAXA = fopen($rt_path, 'w+');
    //     $het_path = temp_filepath();
    //     $HARVEST_EVENTS_TAXA = fopen($het_path, 'w+');
    //     $result = $this->mysqli->query("SELECT he.id, he.name_id, n.string FROM (hierarchy_entries he JOIN names n ON (he.name_id=n.id)) LEFT JOIN taxa t ON (he.id=t.hierarchy_entry_id) WHERE he.hierarchy_id=$this->hierarchy_id");
    //     while($result && $row=$result->fetch_assoc())
    //     {
    //         $guid = Functions::generate_guid();
    //         $string = $this->mysqli->escape($row['string']);
    //         $name_id = $row['name_id'];
    //         $he_id = $row['id'];
    //         fwrite($OUT, "NULL\t$guid\tNULL\tNULL\tNULL\tNULL\tNULL\t$string\t$name_id\t$he_id\tNULL\tNULL\n");
    //     }
    //     fclose($RESOURCES_TAXA);
    //     fclose($HARVEST_EVENTS_TAXA);
    //     @unlink($rt_path);
    //     @unlink($het_path);
    // }
}

?>
