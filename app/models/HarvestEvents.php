<?php
namespace php_active_record;

class HarvestEvent extends ActiveRecord
{
    public static $belongs_to = array(
            array('resource')
        );
    
    public static function delete($id)
    {
        if(!$id) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        $mysqli->delete("DELETE do FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE harvest_event_id=$id AND dohe.status_id IN (". Status::inserted()->id .", ". Status::updated()->id .")");
        $mysqli->delete("DELETE FROM data_objects_harvest_events WHERE harvest_event_id=$id");
        $mysqli->delete("DELETE ti FROM harvest_events_hierarchy_entries hehe JOIN top_images ti ON (hehe.hierarchy_entry_id=ti.hierarchy_entry_id) WHERE harvest_event_id=$id AND hehe.status_id IN (". Status::inserted()->id .", ". Status::updated()->id .")");
        $mysqli->delete("DELETE he FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE harvest_event_id=$id AND hehe.status_id IN (". Status::inserted()->id .", ". Status::updated()->id .")");
        $mysqli->delete("DELETE FROM harvest_events_hierarchy_entries WHERE harvest_event_id=$id");
        $mysqli->delete("DELETE FROM harvest_events WHERE id=$id");
        
        $mysqli->end_transaction();
    }
    
    public function completed()
    {
        $this->completed_at = 'NOW()';
        $this->save();
        $this->refresh();
    }
    
    public function published()
    {
        $this->published_at = 'NOW()';
        $this->save();
        $this->refresh();
    }
    
    public function make_objects_visible($object_guids_to_keep = null)
    {
        $where_clause = '';
        if($object_guids_to_keep) $where_clause = "AND do.guid NOT IN ('". implode($object_guids_to_keep,"','") ."')";
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.visibility_id=". Visibility::visible()->id ." WHERE do.visibility_id=". Visibility::preview()->id ." AND dohe.harvest_event_id=$this->id $where_clause");
    }
    
    public function publish_objects()
    {
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.published=1 WHERE do.published=0 AND dohe.harvest_event_id=$this->id");
    }
    
    public function publish_hierarchy_entries()
    {
        $this->mysqli->update("UPDATE harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.published=1, he.visibility_id=". Visibility::visible()->id .", tc.published=1 WHERE hehe.harvest_event_id=$this->id");
        $this->publish_hierarchy_entry_parents();
        $this->make_hierarchy_entry_parents_visible();
    }
    
    public function publish_hierarchy_entry_parents()
    {
        if($this->resource->hierarchy_id)
        {
            $continue = true;
            while($continue)
            {
                $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) JOIN taxon_concepts tc_parents ON (he_parents.taxon_concept_id=tc_parents.id) SET he_parents.published=1, tc_parents.published=1 WHERE he.hierarchy_id=". $this->resource->hierarchy_id ." AND he.published=1 AND he_parents.published=0");
                
                // continue doing this as long as we're affecting new rows
                $continue = false;
                $result = $this->mysqli->query("SELECT COUNT(*) as count FROM hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) JOIN taxon_concepts tc_parents ON (he_parents.taxon_concept_id=tc_parents.id) WHERE he.hierarchy_id=". $this->resource->hierarchy_id ." AND he.published=1 AND he_parents.published=0");
                if($result && $row=$result->fetch_assoc())
                {
                    $continue = $row['count'];
                }
                // if($continue) echo "Continuing with $continue parents\n";
            }
        }
    }
    
    function make_hierarchy_entry_parents_visible()
    {
        if($this->resource->hierarchy_id)
        {
            $continue = true;
            while($continue)
            {
                $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) SET he_parents.visibility_id=". Visibility::visible()->id ." WHERE he.hierarchy_id=". $this->resource->hierarchy_id ." AND he.visibility_id=". Visibility::visible()->id ." AND he_parents.visibility_id!=". Visibility::visible()->id);
                
                // continue doing this as long as we're affecting new rows
                $continue = false;
                $result = $this->mysqli->query("SELECT COUNT(*) as count FROM hierarchy_entries he JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) WHERE he.hierarchy_id=". $this->resource->hierarchy_id ." AND he.visibility_id=". Visibility::visible()->id ." AND he_parents.visibility_id!=". Visibility::visible()->id);
                if($result && $row=$result->fetch_assoc())
                {
                    $continue = $row['count'];
                }
                // if($continue) echo "Continuing with $continue parents\n";
            }
        }
    }
    
    
    public function vet_objects()
    {
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe STRAIGHT_JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.vetted_id=". Vetted::trusted()->id ." WHERE do.vetted_id=". Vetted::unknown()->id ." AND dohe.harvest_event_id=$this->id");
    }
    
    
    public function inherit_visibilities_from($last_published_harvest_event_id)
    {
        $last_harvest = new HarvestEvent($last_published_harvest_event_id);
        // make sure its in the same resource
        if($last_harvest->resource_id != $this->resource_id) return false;
        // make sure this is newer
        if($last_harvest->id > $this->id) return false;
        
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT do_current.id, do_previous.visibility_id FROM (data_objects_harvest_events dohe_previous JOIN data_objects do_previous ON (dohe_previous.data_object_id=do_previous.id)) JOIN (data_objects_harvest_events dohe_current JOIN data_objects do_current ON (dohe_current.data_object_id=do_current.id)) ON (dohe_previous.guid=dohe_current.guid AND dohe_previous.data_object_id!=dohe_current.data_object_id) WHERE dohe_previous.harvest_event_id=$last_harvest->id AND dohe_current.harvest_event_id=$this->id AND do_previous.visibility_id IN (".Visibility::invisible()->id.", ".Visibility::inappropriate()->id.")");
        
        $FILE = fopen($outfile, "r");
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $values = explode("\t", trim($line));
                $data_object_id = $values[0];
                $visibility_id = $values[1];
                $GLOBALS['db_connection']->update("UPDATE data_objects SET visibility_id=$visibility_id WHERE id=$data_object_id");
            }
        }
        fclose($FILE);
        unlink($outfile);
    }
    
    public function insert_top_images()
    {
        $image_type_id = DataType::image()->id;
        
        // Published images go to top_images
        $outfile = $this->mysqli->select_into_outfile("SELECT he.id, do.id, 255 FROM data_objects_harvest_events dohevt JOIN data_objects do ON (dohevt.data_object_id=do.id) JOIN data_objects_hierarchy_entries dohe ON (dohevt.data_object_id=dohe.data_object_id) JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id) WHERE dohevt.harvest_event_id=$this->id AND do.data_type_id=$image_type_id AND do.published=1 AND do.visibility_id=".Visibility::visible()->id." AND (he.published=1 OR he.visibility_id=".Visibility::preview()->id.")");
        $GLOBALS['db_connection']->load_data_infile($outfile, 'top_images');
        unlink($outfile);
        
        // Published images go to top_concept_images
        $outfile = $this->mysqli->select_into_outfile("SELECT he.taxon_concept_id, do.id, 255 FROM data_objects_harvest_events dohevt JOIN data_objects do ON (dohevt.data_object_id=do.id) JOIN data_objects_hierarchy_entries dohe ON (dohevt.data_object_id=dohe.data_object_id) JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id) WHERE dohevt.harvest_event_id=$this->id AND do.data_type_id=$image_type_id AND do.published=1 AND do.visibility_id=".Visibility::visible()->id." AND (he.published=1 OR he.visibility_id=".Visibility::preview()->id.")");
        $GLOBALS['db_connection']->load_data_infile($outfile, 'top_concept_images');
        unlink($outfile);
        
        // Published XOR visible images go to top_unpublished_images
        $outfile = $this->mysqli->select_into_outfile("SELECT he.id, do.id, 255 FROM data_objects_harvest_events dohevt JOIN data_objects do ON (dohevt.data_object_id=do.id) JOIN data_objects_hierarchy_entries dohe ON (dohevt.data_object_id=dohe.data_object_id) JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id) WHERE dohevt.harvest_event_id=$this->id AND do.data_type_id=$image_type_id AND do.visibility_id=".Visibility::preview()->id." AND (he.published=1 OR he.visibility_id=".Visibility::preview()->id.")");
        $GLOBALS['db_connection']->load_data_infile($outfile, 'top_unpublished_images');
        unlink($outfile);
        
        // Published XOR visible images go to top_unpublished_concept_images
        $outfile = $this->mysqli->select_into_outfile("SELECT he.taxon_concept_id, do.id, 255 FROM data_objects_harvest_events dohevt JOIN data_objects do ON (dohevt.data_object_id=do.id) JOIN data_objects_hierarchy_entries dohe ON (dohevt.data_object_id=dohe.data_object_id) JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id) WHERE dohevt.harvest_event_id=$this->id AND do.data_type_id=$image_type_id AND do.visibility_id!=".Visibility::preview()->id." AND (he.published=1 OR he.visibility_id=".Visibility::preview()->id.")");
        $GLOBALS['db_connection']->load_data_infile($outfile, 'top_unpublished_concept_images');
        unlink($outfile);
        
    }
    
    public function add_hierarchy_entry($hierarchy_entry, $status)
    {
        if(@!$hierarchy_entry->id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO harvest_events_hierarchy_entries VALUES ($this->id, $hierarchy_entry->id, '$hierarchy_entry->guid', ". Status::find_or_create_by_translated_label($status)->id .")");
    }
    
    public function add_data_object($data_object, $status)
    {
        if(@!$data_object->id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_harvest_events VALUES ($this->id, $data_object->id, '$data_object->guid', ". Status::find_or_create_by_translated_label($status)->id .")");
    }
}

?>