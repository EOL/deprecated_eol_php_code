<?php

class Resource extends MysqlBase
{
    public $harvest_event;
    public $last_harvest_event;
    public $resource_path;
    public $start_harvest_time;
    public $end_harvest_time;
    
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
        
        $this->harvest_event = false;
        $this->resource_path = CONTENT_RESOURCE_LOCAL_PATH.$this->id.".xml";
    }
    
    public static function delete($id)
    {
        if(!$id) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();

        //$mysqli->delete("DELETE do FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE ado FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN agents_data_objects ado ON (dohe.data_object_id=ado.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dot FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_taxa dot ON (dohe.data_object_id=dot.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dor FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_refs dor ON (dohe.data_object_id=dor.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE ado FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN audiences_data_objects ado ON (dohe.data_object_id=ado.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE doii FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_info_items doii ON (dohe.data_object_id=doii.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dotoc FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects_table_of_contents dotoc ON (dohe.data_object_id=dotoc.data_object_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE dohe FROM harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) WHERE he.resource_id=$id");
        
        $mysqli->delete("DELETE t FROM harvest_events he STRAIGHT_JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id) JOIN taxa t ON (het.taxon_id=t.id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE rt FROM harvest_events he STRAIGHT_JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id) JOIN refs_taxa rt  ON (het.taxon_id=rt.taxon_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE cnt FROM harvest_events he STRAIGHT_JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id) JOIN common_names_taxa cnt  ON (het.taxon_id=cnt.taxon_id) WHERE he.resource_id=$id");
        $mysqli->delete("DELETE het FROM harvest_events he STRAIGHT_JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id) WHERE he.resource_id=$id");
        
        $mysqli->delete("DELETE FROM harvest_events WHERE resource_id=$id");
        $mysqli->delete("DELETE FROM resources WHERE id=$id");
        
        $mysqli->end_transaction();
    }
    
    public function auto_publish()
    {
        if($this->auto_publish || $this->content_partner()->auto_publish) return true;
        
        return false;
    }
    
    public function vetted()
    {
        if($this->vetted || $this->content_partner()->vetted) return true;
        
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
        
        //Adding 12 hours to last modified to offset time it takes to update some resources
        $last_updated = Functions::file_hours_since_modified($this->resource_file_path()) + 12;
        if($last_updated < $this->refresh_period_hours) return false;
        
        return true;
    }
    
    // will return boolean of THIS resource is ready
    public function ready_to_harvest($hours_ahead_of_time = null)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $extra_hours_clause = "";
        if($hours_ahead_of_time) $extra_hours_clause = " - $hours_ahead_of_time";
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM resources WHERE id=$this->id AND (resource_status_id=".ResourceStatus::insert("Force Harvest")." OR (harvested_at IS NULL AND (resource_status_id=".ResourceStatus::insert("Validated")." OR resource_status_id=".ResourceStatus::insert("Validation Failed")." OR resource_status_id=".ResourceStatus::insert("Processing Failed").")) OR (refresh_period_hours!=0 AND DATE_ADD(harvested_at, INTERVAL (refresh_period_hours $extra_hours_clause) HOUR)<=NOW() AND resource_status_id IN (".ResourceStatus::insert("Validated").", ".ResourceStatus::insert("Validation Failed").", ".ResourceStatus::insert("Processed").", ".ResourceStatus::insert("Processing Failed").", ".ResourceStatus::insert("Published").")))");
        
        if($result && $row=$result->fetch_assoc()) return true;
        return false;
    }
    
    // static method to find ALL resources ready
    public static function ready_for_harvesting()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $resources = array();
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM resources WHERE resource_status_id=".ResourceStatus::insert("Force Harvest")." OR (harvested_at IS NULL AND (resource_status_id=".ResourceStatus::insert("Validated")." OR resource_status_id=".ResourceStatus::insert("Validation Failed")." OR resource_status_id=".ResourceStatus::insert("Processing Failed").")) OR (refresh_period_hours!=0 AND DATE_ADD(harvested_at, INTERVAL refresh_period_hours HOUR)<=NOW() AND resource_status_id IN (".ResourceStatus::insert("Upload Failed").", ".ResourceStatus::insert("Validated").", ".ResourceStatus::insert("Validation Failed").", ".ResourceStatus::insert("Processed").", ".ResourceStatus::insert("Processing Failed").", ".ResourceStatus::insert("Published")."))");
        while($result && $row=$result->fetch_assoc())
        {
            $resources[] = $resource = new Resource($row["id"]);
        }
        
        return $resources;
    }
    
    public static function ready_for_publishing()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        $resources = array();
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM resources WHERE resource_status_id=". ResourceStatus::insert("Publish Pending"));
        while($result && $row=$result->fetch_assoc())
        {
            $resources[] = $resource = new Resource($row["id"]);
        }
        return $resources;
    }
    
    public function content_partner()
    {
        if(@$this->content_partner) return $this->content_partner;
        
        $this->content_partner = new ContentPartner(ContentPartner::find($this->data_supplier()->id));
        return $this->content_partner;
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
        
        $result = $this->mysqli->query("SELECT agent_id FROM agents_resources WHERE resource_id=$this->id AND resource_agent_role_id=".ResourceAgentRole::insert("Data Supplier"));
        if($result && $row=$result->fetch_assoc())
        {
            $this->data_supplier = new Agent($row["agent_id"]);
        }else $this->data_supplier = 0;
        
        return $this->data_supplier;
    }
    
    public function add_taxon($taxon_id, $taxon)
    {
        if(!$taxon_id) return 0;
        $identifier = @$this->mysqli->escape($taxon->identifier);
        $source_url = @$this->mysqli->escape($taxon->source_url);
        $created_at = @$this->mysqli->escape($taxon->created_at);
        $modified_at = @$this->mysqli->escape($taxon->modified_at);
        $this->mysqli->insert("INSERT INTO resources_taxa VALUES ($this->id, $taxon_id, '$identifier', '$source_url', '$created_at', '$modified_at')");
    }
    
    public function unpublish_data_objects()
    {
        $this->mysqli->update("UPDATE harvest_events he STRAIGHT_JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) STRAIGHT_JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.published=0 WHERE do.published=1 AND he.resource_id=$this->id");
    }
    
    public function unpublish_hierarchy_entries()
    {
        $this->mysqli->update("UPDATE hierarchies_resources hr JOIN hierarchy_entries he ON (hr.hierarchy_id=he.hierarchy_id)  SET he.published=0, he.visibility_id=0 WHERE he.published=1 AND hr.resource_id=$this->id");
    }
    
    public function unpublish_taxon_concepts()
    {
        $this->mysqli->update("UPDATE hierarchies_resources hr JOIN hierarchies h ON (hr.hierarchy_id=h.id) JOIN hierarchy_entries he ON (h.id=he.hierarchy_id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.published=0 WHERE hr.resource_id=$this->id");
    }
    
    
    public function publish()
    {
        if($this->resource_status_id != ResourceStatus::insert("Publish Pending")) return false;
        $this->mysqli->begin_transaction();
        
        if($harvest_event_id = $this->most_recent_harvest_event_id())
        {
            $harvest_event = new HarvestEvent($harvest_event_id);
            if($harvest_event->published_at) return true;
            if(!$harvest_event->completed_at) return false;
            
            // make all objects in last harvest visible if they were in preview mode
            $harvest_event->make_objects_visible();
            
            // preserve visibilities from older versions of same objects
            // if the older versions were curated they may be invible or inappropriate and we don't want to lose that info
            if($last_id = $this->most_recent_published_harvest_event_id()) $harvest_event->inherit_visibilities_from($last_id);
            
            // set published=0 for ALL objects associated with this resource
            $this->unpublish_data_objects();
            
            // now only set published=1 for the objects in the latest harvest
            $harvest_event->publish_objects();
            
            // set the harvest published_at date
            $harvest_event->published();
            
            
            if($hierarchy_id = $this->hierarchy_id())
            {
                $catalogue_of_life_id = Hierarchy::find_by_agent_id(Agent::find("Catalogue of Life"));
                
                // unpublish all concepts associated with this resource
                $this->unpublish_taxon_concepts();
                $this->unpublish_hierarchy_entries();
                
                // make sure all COL concepts are published
                $this->mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.published=1 WHERE he.hierarchy_id=$catalogue_of_life_id AND tc.published=0 AND tc.supercedure_id=0");
                
                // now set published=1 for all concepts in the latest harvest
                $harvest_event->publish_taxon_concepts();
                $harvest_event->publish_hierarchy_entries();
                
                CompareHierarchies::begin_concept_assignment($hierarchy_id);
            }
            
            $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert("Published").", notes='harvest published' WHERE id=$this->id");
        }
        $this->mysqli->end_transaction();
    }
    
    public function harvest()
    {
        Functions::debug("Starting harvest of resource: $this->id");
        Functions::debug("Validating resource: $this->id");
        $valid = $this->validate($this->resource_path);
        Functions::debug("Validated resource: $this->id");
        if($valid)
        {
            $this->mysqli->begin_transaction();
            
            $this->start_harvest();
            
            Functions::debug("Parsing resource: $this->id");
            $connection = new SchemaConnection($this);
            SchemaParser::parse($this->resource_path, $connection);
            unset($connection);
            Functions::debug("Parsed resource: $this->id");
            
            $this->end_harvest();
            $this->mysqli->commit();
            
            $this->update_names_of_new_entries();
            $this->mysqli->commit();
            
            // if there are things in preview mode in old harvest which are not in this harvest
            // then set them to be invisible
            $this->make_old_preview_objects_invisible();
            $this->mysqli->commit();
            
            if($hierarchy_id = $this->hierarchy_id())
            {
                $catalogue_of_life_id = Hierarchy::find_by_agent_id(Agent::find("Catalogue of Life"));
                
                Functions::debug("Assigning nested set values resource: $this->id");
                Tasks::rebuild_nested_set($hierarchy_id);
                Functions::debug("Finished assigning: $this->id");
                
                // Rebuild the Solr index for this hierarchy
                $indexer = new HierarchyEntryIndexer();
                $indexer->index($hierarchy_id);
                
                // Compare this hierarchy to all others and store the results in the hierarchy_entry_relationships table
                $hierarchy = new Hierarchy($hierarchy_id);
                CompareHierarchies::process_hierarchy($hierarchy, null, true);
                $this->make_new_hierarchy_entries_preview($hierarchy);
                
                CompareHierarchies::begin_concept_assignment($hierarchy_id);
                
                if($this->vetted())
                {
                    // Vet all taxa associated with this resource
                    $this->mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.vetted_id=".Vetted::insert("Trusted").", tc.vetted_id=".Vetted::insert("Trusted")." WHERE hierarchy_id=$hierarchy_id");
                }
            }
            
            if($this->vetted() && $this->harvest_event)
            {
                // set vetted=trusted for all objects in this harvest
                $this->harvest_event->vet_objects();
            }
            
            $this->mysqli->end_transaction();
            
            if($this->auto_publish())
            {
                $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert("Publish Pending")." WHERE id=$this->id");
            }
        }
    }
    
    public function update_names_of_new_entries()
    {
        if($this->harvest_event)
        {
            $last_harvest_max_he_id = 0;
            if($last_he_id = $this->last_harvest_event_id())
            {
                $result = $this->mysqli->query("SELECT max(hierarchy_entry_id) max FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) WHERE het.harvest_event_id=$last_he_id");
                if($result && $row=$result->fetch_assoc()) $last_harvest_max_he_id = $row['max'];
            }
            $result = $this->mysqli->query("SELECT DISTINCT taxon_concept_id FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) WHERE het.harvest_event_id=".$this->harvest_event->id." AND t.hierarchy_entry_id>$last_harvest_max_he_id");
            while($result && $row=$result->fetch_assoc())
            {
                Tasks::update_taxon_concept_names($row['taxon_concept_id']);
            }
        }
    }
    
    public function make_new_hierarchy_entries_preview($hierarchy)
    {
        if($this->harvest_event)
        {
            $this->mysqli->update("UPDATE harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) SET he.visibility_id=". Visibility::insert('preview') ." WHERE het.harvest_event_id=".$this->harvest_event->id." AND he.visibility_id=0");
            $this->make_new_hierarchy_entries_parents_preview($hierarchy);
        }
    }
    
    public function make_new_hierarchy_entries_parents_preview($hierarchy)
    {
        $result = $this->mysqli->query("SELECT 1 FROM hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) WHERE he.hierarchy_id=$hierarchy->id AND he.visibility_id=". Visibility::insert('preview') ." AND he_parents.visibility_id=0 LIMIT 1");
        while($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) SET he_parents.visibility_id=". Visibility::insert('preview') ." WHERE he.hierarchy_id=$hierarchy->id AND he.visibility_id=". Visibility::insert('preview') ." AND he_parents.visibility_id=0");
            
            $result = $this->mysqli->query("SELECT 1 FROM hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) WHERE he.hierarchy_id=$hierarchy->id AND he.visibility_id=". Visibility::insert('preview') ." AND he_parents.visibility_id=0 LIMIT 1");
        }
    }
    
    public function make_old_preview_objects_invisible()
    {
        if($this->harvest_event)
        {
            $result = $this->mysqli->query("SELECT COUNT(*) as count FROM (harvest_events he1 JOIN data_objects_harvest_events dohe1 ON (he1.id=dohe1.harvest_event_id) JOIN data_objects do1 ON (dohe1.data_object_id=do1.id)) LEFT JOIN data_objects_harvest_events dohe2 ON (do1.id=dohe2.data_object_id AND dohe2.harvest_event_id=".$this->harvest_event->id.") WHERE he1.resource_id=$this->id AND he1.id!=".$this->harvest_event->id." AND do1.visibility_id=". Visibility::find('preview') ." AND dohe2.data_object_id IS NULL");
            if($result && $row=$result->fetch_assoc())
            {
                if($row["count"]) $this->mysqli->query("UPDATE (harvest_events he1 JOIN data_objects_harvest_events dohe1 ON (he1.id=dohe1.harvest_event_id) JOIN data_objects do1 ON (dohe1.data_object_id=do1.id)) LEFT JOIN data_objects_harvest_events dohe2 ON (do1.id=dohe2.data_object_id AND dohe2.harvest_event_id=".$this->harvest_event->id.") SET do1.visibility_id=0 WHERE he1.resource_id=$this->id AND he1.id!=".$this->harvest_event->id." AND do1.visibility_id=". Visibility::find('preview') ." AND dohe2.data_object_id IS NULL");
            }
        }
    }
    
    public function start_harvest()
    {
        if(!$this->harvest_event)
        {
            // Set resource as 'Being Processed'
            $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert("Being Processed")." WHERE id=$this->id");
            
            // Create this harvest event
            $this->harvest_event = new HarvestEvent(HarvestEvent::insert($this->id));
            $this->start_harvest_time  = date('Y m d H');
        }
    }
    
    public function end_harvest()
    {
        if($this->harvest_event)
        {
            $this->harvest_event->completed();
            $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert("Processed").", harvested_at=NOW(), notes='harvest ended' WHERE id=$this->id");
            $this->end_harvest_time  = date('Y m d H');
            
            // Make sure we set a harvest start time
            // Compare the end time to the start time, get the number of hours difference,
            // and sync the content servers for each hour this resource was being processed.
            if($this->start_harvest_time)
            {
                $d1 = explode(" ", $this->start_harvest_time);
                $d2 = explode(" ", $this->end_harvest_time);
                
                Functions::debug("Start harvest time: $this->start_harvest_time");
                Functions::debug("End harvest time: $this->end_harvest_time");
                
                $time1 = mktime($d1[3], 0, 0, $d1[1], $d1[2], $d1[0]);
                $time2 = mktime($d2[3], 0, 0, $d2[1], $d2[2], $d2[0]);
                
                $harvest_hours = ceil(($time2 - $time1) / 3600);
                
                Functions::debug("Harvest hours: $harvest_hours");
                
                $date = explode(" ", date("Y m d H", mktime($d1[3], 0, 0, $d1[1], $d1[2], $d1[0])));
                ContentManager::sync_to_content_servers($date[0], $date[1], $date[2], $date[3]);
                while($harvest_hours)
                {
                    $d1[3]+=1;
                    $date = explode(" ", date("Y m d H", mktime($d1[3], 0, 0, $d1[1], $d1[2], $d1[0])));
                    ContentManager::sync_to_content_servers($date[0], $date[1], $date[2], $date[3]);
                    $harvest_hours--;
                }
            }
        }
    }
    
    public function validate()
    {
        $validation_result = SchemaValidator::validate($this->resource_path);
        if($validation_result!==true)
        {
            $error_string = $this->mysqli->escape(implode("<br>", $validation_result));
            $this->mysqli->update("UPDATE resources SET notes='$error_string', resource_status_id=".ResourceStatus::insert("Processing Failed")." WHERE id=$this->id");
            return false;
        }
        
        unset($validator);
        
        return true;
    }
    
    public function change_status($status)
    {
        $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert($status)." WHERE id=$this->id");
    }
    
    public function insert_hierarchy()
    {
        if($hierarchy_id = $this->hierarchy_id()) return $hierarchy_id;
        
        $provider_agent = $this->data_supplier();
        
        $params = array();
        if(@$provider_agent->id) $params["agent_id"] = $provider_agent->id;
        $params["label"] = $this->title;
        $params["description"] = "From resource $this->title";
        $hierarchy_mock = Functions::mock_object("Hierarchy", $params);
        $hierarchy_id = Hierarchy::insert($hierarchy_mock);
        
        $this->mysqli->insert("INSERT INTO hierarchies_resources VALUES ($this->id, $hierarchy_id)");
        
        return $hierarchy_id;
    }
    
    public function hierarchy_id()
    {
        $result = $this->mysqli->query("SELECT hierarchy_id FROM hierarchies_resources WHERE resource_id=$this->id");
        if($result && $row=$result->fetch_assoc()) return $row["hierarchy_id"];
        
        return 0;
    }

    static function insert($parameters)
    {
        if($result = self::find($parameters)) return $result;
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($parameters)
    {
        return 0;
    }
    
    static function find_by_title($string)
    {
        return parent::find_by("title", $string, Functions::class_name(__FILE__));
    }
    
}

?>