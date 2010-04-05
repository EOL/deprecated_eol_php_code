<?php

class HarvestEvent extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public static function all()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        $all = array();
        $result = $mysqli->query("SELECT * FROM harvest_events");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new HarvestEvent($row);
        }
        return $all;
    }
    
    public static function delete($id)
    {
        if(!$id) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        $mysqli->delete("DELETE do FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE harvest_event_id=$id AND dohe.status_id IN (".Status::insert("Inserted").", ".Status::insert("Updated").")");
        $mysqli->delete("DELETE FROM data_objects_harvest_events WHERE harvest_event_id=$id");
        $mysqli->delete("DELETE ti FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN top_images ti ON (t.hierarchy_entry_id=ti.hierarchy_entry_id) WHERE harvest_event_id=$id AND het.status_id IN (".Status::insert("Inserted").", ".Status::insert("Updated").")");
        $mysqli->delete("DELETE he FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) WHERE harvest_event_id=$id AND het.status_id IN (".Status::insert("Inserted").", ".Status::insert("Updated").")");
        $mysqli->delete("DELETE t FROM harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) WHERE harvest_event_id=$id AND het.status_id IN (".Status::insert("Inserted").", ".Status::insert("Updated").")");
        $mysqli->delete("DELETE FROM harvest_events_taxa WHERE harvest_event_id=$id");
        $mysqli->delete("DELETE FROM harvest_events WHERE id=$id");
        
        $mysqli->end_transaction();
    }
    
    function resource()
    {
        if(@$this->resource) return $this->resource;
        
        $this->resource = new Resource($this->resource_id);
        return $this->resource;
    }
    
    public function completed()
    {
        //$this->expire_taxa_cache();
        $this->mysqli->update("UPDATE harvest_events SET completed_at=NOW() WHERE id=$this->id");
    }
    
    public function published()
    {
        $this->mysqli->update("UPDATE harvest_events SET published_at=NOW() WHERE id=$this->id");
    }
    
    public function make_objects_visible($object_guids_to_keep = null)
    {
        $where_clause = '';
        if($object_guids_to_keep) $where_clause = "AND do.guid NOT IN ('". implode($object_guids_to_keep,"','") ."')";
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.visibility_id=". Visibility::insert('Visible') ." WHERE do.visibility_id=". Visibility::insert('Preview') ." AND dohe.harvest_event_id=$this->id $where_clause");
    }
    
    public function publish_objects()
    {
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.published=1 WHERE do.published=0 AND dohe.harvest_event_id=$this->id");
    }
    
    public function publish_hierarchy_entries()
    {
        $this->mysqli->update("UPDATE harvest_events_taxa het JOIN taxa t ON (het.taxon_id=t.id) JOIN hierarchy_entries he ON (t.hierarchy_entry_id=he.id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.published=1, he.visibility_id=". Visibility::insert('visible') .", tc.published=1 WHERE het.harvest_event_id=$this->id");
        $this->publish_hierarchy_entry_parents();
        $this->make_hierarchy_entry_parents_visible();
    }
    
    public function publish_hierarchy_entry_parents()
    {
        if($hierarchy_id = $this->resource()->hierarchy_id())
        {
            $continue = true;
            while($continue)
            {
                $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) JOIN taxon_concepts tc_parents ON (he_parents.taxon_concept_id=tc_parents.id) SET he_parents.published=1, tc_parents.published=1 WHERE he.hierarchy_id=$hierarchy_id AND he.published=1 AND he_parents.published=0");
                
                // continue doing this as long as we're effecting new rows
                $continue = $this->mysqli->affected_rows();
                if($continue) echo "Continuing with $continue parents\n";
            }
        }
    }
    
    function make_hierarchy_entry_parents_visible()
    {
        if($hierarchy_id = $this->resource()->hierarchy_id())
        {
            $continue = true;
            while($continue)
            {
                $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) SET he_parents.visibility_id=". Visibility::insert('visible') ." WHERE he.hierarchy_id=$hierarchy_id AND he.visibility_id=". Visibility::insert('visible') ." AND he_parents.visibility_id!=". Visibility::insert('visible'));
                
                // continue doing this as long as we're effecting new rows
                $continue = $this->mysqli->affected_rows();
                if($continue) echo "Continuing with $continue parents\n";
            }
        }
    }
    
    
    public function vet_objects()
    {
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe STRAIGHT_JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.vetted_id=". Vetted::insert('Trusted') ." WHERE do.vetted_id=". Vetted::insert('unknown') ." AND dohe.harvest_event_id=$this->id");
    }
    
    
    public function inherit_visibilities_from($last_published_harvest_event_id)
    {
        $last_harvest = new HarvestEvent($last_published_harvest_event_id);
        // make sure its in the same resource
        if($last_harvest->resource_id != $this->resource_id) return false;
        // make sure this is newer
        if($last_harvest->id > $this->id) return false;
        
        $this->mysqli->query("UPDATE (data_objects_harvest_events dohe_previous JOIN data_objects do_previous ON (dohe_previous.data_object_id=do_previous.id)) JOIN (data_objects_harvest_events dohe_current JOIN data_objects do_current ON (dohe_current.data_object_id=do_current.id)) ON (dohe_previous.guid=dohe_current.guid AND dohe_previous.data_object_id!=dohe_current.data_object_id) SET do_current.visibility_id = do_previous.visibility_id WHERE dohe_previous.harvest_event_id=$last_harvest->id AND dohe_current.harvest_event_id=$this->id AND do_previous.visibility_id IN (".Visibility::insert('Invisible').", ".Visibility::insert('Inappropriate').")");
    }
    
    public function expire_taxa_cache()
    {
        $taxon_ids = $this->modified_taxon_ids();
        
        if(defined("TAXON_CACHE_PREFIX"))
        {
            $response = Functions::curl_post_request(TAXON_CACHE_PREFIX, array("taxa_ids" => implode(",", $taxon_ids)));
            debug($response);
        }
    }
    
    public function modified_taxon_ids()
    {
        $taxon_ids = array();
        
        $result = $this->mysqli->query("SELECT DISTINCT dot.taxon_id FROM data_objects_harvest_events dohe JOIN data_objects_taxa dot ON (dohe.data_object_id=dot.data_object_id) WHERE dohe.harvest_event_id=$this->id AND dohe.status_id!=".Status::insert("Unchanged"));
        while($result && $row=$result->fetch_assoc())
        {
            $taxon_ids[] = $row["taxon_id"];
        }
        
        return $taxon_ids;
    }
    
    public function add_taxon($taxon, $status)
    {
        if(@!$taxon->id) return false;
        $this->mysqli->insert("INSERT INTO harvest_events_taxa VALUES ($this->id, $taxon->id, '$taxon->guid', ".Status::insert($status).")");
    }
    
    public function add_data_object($data_object, $status)
    {
        if(@!$data_object->id) return false;
        $this->mysqli->insert("INSERT INTO data_objects_harvest_events VALUES ($this->id, $data_object->id, '$data_object->guid', ".Status::insert($status).")");
    }
    
    static function insert($resource_id)
    {
        if(!$resource_id) return 0;
        
        return parent::insert_fields_into(array('resource_id' => $resource_id), Functions::class_name(__FILE__));
    }
    
    static function find($parameters)
    {
        return 0;
    }
}

?>