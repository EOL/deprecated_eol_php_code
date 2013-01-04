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
    
    public function previous_harvest_event()
    {
        $result = $this->mysqli->query("SELECT SQL_NO_CACHE MAX(id) as id FROM harvest_events WHERE resource_id = $this->resource_id AND id < $this->id");
        if($result && $row=$result->fetch_assoc())
        {
            if($row["id"]) return HarvestEvent::find($row["id"]);
        }
        return null;
    }
    
    public function make_objects_visible()
    {
        $this->mysqli->query("
            UPDATE data_objects_harvest_events dohe
            JOIN data_objects do ON (dohe.data_object_id=do.id)
            JOIN data_objects_hierarchy_entries dohent ON (do.id=dohent.data_object_id)
            SET dohent.visibility_id=". Visibility::visible()->id ."
            WHERE dohent.visibility_id=". Visibility::preview()->id ."
            AND dohe.harvest_event_id=$this->id");
    }
    
    public function publish_objects()
    {
        $this->mysqli->query("UPDATE data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) SET do.published=1 WHERE do.published=0 AND dohe.harvest_event_id=$this->id");
    }
    
    public function publish_hierarchy_entries()
    {
        $this->mysqli->update("UPDATE harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.published=1, he.visibility_id=". Visibility::visible()->id .", tc.published=1 WHERE hehe.harvest_event_id=$this->id");
        $this->mysqli->update("UPDATE harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) JOIN synonyms s ON (he.id=s.hierarchy_entry_id AND he.hierarchy_id=s.hierarchy_id) SET s.published=1 WHERE hehe.harvest_event_id=$this->id");
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
        $this->mysqli->query("
            UPDATE data_objects_harvest_events dohe
            STRAIGHT_JOIN data_objects do ON (dohe.data_object_id=do.id)
            JOIN data_objects_hierarchy_entries dohent ON (do.id=dohent.data_object_id)
            SET dohent.vetted_id=". Vetted::trusted()->id ."
            WHERE dohent.vetted_id=". Vetted::unknown()->id ."
            AND dohe.harvest_event_id=$this->id");
    }
    
    
    public function inherit_visibilities_from($last_published_harvest_event_id)
    {
        $last_harvest = new HarvestEvent($last_published_harvest_event_id);
        // make sure its in the same resource
        if($last_harvest->resource_id != $this->resource_id) return false;
        // make sure this is newer
        if($last_harvest->id > $this->id) return false;
        
        $outfile = $GLOBALS['db_connection']->select_into_outfile("
            SELECT do_current.id, dohent_previous.visibility_id, dohent_previous.hierarchy_entry_id
            FROM
                (data_objects_harvest_events dohe_previous
                JOIN data_objects do_previous ON (dohe_previous.data_object_id=do_previous.id)
                JOIN data_objects_hierarchy_entries dohent_previous ON (dpo_previous.id=dohent_previous.data_object_id))
            JOIN
                (data_objects_harvest_events dohe_current
                JOIN data_objects do_current ON (dohe_current.data_object_id=do_current.id))
            ON (dohe_previous.guid=dohe_current.guid AND dohe_previous.data_object_id!=dohe_current.data_object_id)
            WHERE dohe_previous.harvest_event_id=$last_harvest->id
            AND dohe_current.harvest_event_id=$this->id
            AND dohent_previous.visibility_id IN (".Visibility::invisible()->id.")");
        
        $FILE = fopen($outfile, "r");
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $values = explode("\t", trim($line));
                $data_object_id = $values[0];
                $visibility_id = $values[1];
                $hierarchy_entry_id = $values[2];
                $GLOBALS['db_connection']->update("UPDATE data_objects_hierarchy_entries SET visibility_id=$visibility_id WHERE data_object_id=$data_object_id AND hierarchy_entry_id=$hierarchy_entry_id");
            }
        }
        fclose($FILE);
        unlink($outfile);
    }
    
    public function add_taxon_from_harvest($parameters = array())
    {
        $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($taxon_parameters, $this->resource->hierarchy_id);
        if(@!$hierarchy_entry->id) return false;
        $this->hierarchy_entry_ids[$taxon_parameters["identifier"]] = $hierarchy_entry->id;
        $this->resource->add_hierarchy_entry($hierarchy_entry, 'inserted');
        
        $hierarchy_entry = HierarchyEntry::create_entries_for_taxon($t, $this->resource->hierarchy_id);
        if(@!$hierarchy_entry->id) return false;
        
        $this->resource->harvest_event->add_hierarchy_entry($hierarchy_entry, 'inserted');
        
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
    
    public function index_for_search($comparison_harvest_event_id = null)
    {
        $search_indexer = new SiteSearchIndexer();
        if($comparison_harvest_event_id)
        {
            $query = "SELECT dohe.data_object_id
                FROM data_objects_harvest_events dohe
                LEFT JOIN data_objects_harvest_events dohe2 ON (dohe.data_object_id = dohe2.data_object_id AND dohe2.harvest_event_id = $comparison_harvest_event_id)
                WHERE dohe.harvest_event_id = $this->id AND dohe2.data_object_id IS NULL";
        }else
        {
            $query = "SELECT data_object_id FROM data_objects_harvest_events WHERE harvest_event_id = $this->id";
        }
        $data_object_ids = array();
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row) $data_object_ids[] = $row[0];
        if($GLOBALS['ENV_DEBUG']) print_r($data_object_ids);
        if($data_object_ids) $search_indexer->index_type('DataObject', 'data_objects', 'lookup_objects', $data_object_ids);
        
        $object_indexer = new DataObjectAncestriesIndexer();
        $object_indexer->index_objects($data_object_ids);
        
        if($comparison_harvest_event_id)
        {
            $query = "SELECT he.taxon_concept_id
                FROM harvest_events_hierarchy_entries hehe
                JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id)
                LEFT JOIN harvest_events_hierarchy_entries hehe2 ON (hehe.hierarchy_entry_id=hehe2.hierarchy_entry_id AND hehe2.harvest_event_id = $comparison_harvest_event_id)
                WHERE hehe.harvest_event_id = $this->id AND hehe2.hierarchy_entry_id IS NULL";
        }else
        {
            $query = "SELECT he.taxon_concept_id
                FROM harvest_events_hierarchy_entries hehe
                JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id)
                WHERE hehe.harvest_event_id = $this->id";
        }
        $taxon_concept_ids = array();
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row) $taxon_concept_ids[] = $row[0];
        if($GLOBALS['ENV_DEBUG']) print_r($taxon_concept_ids);
        if($taxon_concept_ids) $search_indexer->index_type('TaxonConcept', 'taxon_concepts', 'index_taxa', $taxon_concept_ids);
    }
    
    public function create_collection()
    {
        $collection_title = $this->resource->title;
        $description = trim($this->resource->content_partner->description);
        if($description && !preg_match("/\.$/", $description)) $description = trim($description) . ".";
        $description .= " Last indexed ". date('F j, Y', strtotime($this->completed_at));
        $collection = Collection::find_or_create(array(
            'name' => $collection_title,
            'logo_cache_url' => $this->resource->content_partner->user->logo_cache_url,
            'description' => trim($description),
            'created_at' => 'NOW()',
            'updated_at' => 'NOW()'));
        $GLOBALS['db_connection']->insert("INSERT IGNORE INTO collections_users (collection_id, user_id) VALUES ($collection->id, ".$this->resource->content_partner->user->id.")");
        
        $this->add_objects_to_collection($collection);
        $this->add_taxa_to_collection($collection);
        
        if($this->published_at)
        {
            $collection->published = 1;
            $collection->save();
            $this->resource->set_collection($collection);
        }else
        {
            $collection->published = 0;
            $collection->save();
            $this->resource->set_collection($collection, true);
        }
        
        
        $indexer = new CollectionItemIndexer();
        $indexer->index_collection($collection->id);
        if($this->published_at)
        {
            $indexer = new SiteSearchIndexer();
            $indexer->index_collection($collection);
        }
    }
    
    function count_data_objects()
    {
        $result = $this->mysqli->query("SELECT count(*) as count FROM data_objects_harvest_events WHERE harvest_event_id=$this->id");
        if($result && $row=$result->fetch_assoc())
        {
            return $row['count'];
        }
        return 0;
    }
    
    function count_hierarchy_entries()
    {
        $result = $this->mysqli->query("SELECT count(*) as count FROM harvest_events_hierarchy_entries WHERE harvest_event_id=$this->id");
        if($result && $row=$result->fetch_assoc())
        {
            return $row['count'];
        }
        return 0;
    }
    
    function percent_different_data_objects()
    {
        $previous_harvest_event = $this->previous_harvest_event();
        if(!$previous_harvest_event) return 0;
        $my_count = $this->count_data_objects();
        $previous_count = $previous_harvest_event->count_data_objects();
        if($previous_count == 0) return 0;
        return ((($my_count - $previous_count) / $previous_count) * 100);
    }
    
    function percent_different_hierarchy_entries()
    {
        $previous_harvest_event = $this->previous_harvest_event();
        if(!$previous_harvest_event) return 0;
        $my_count = $this->count_hierarchy_entries();
        $previous_count = $previous_harvest_event->count_hierarchy_entries();
        if($previous_count == 0) return 0;
        return ((($my_count - $previous_count) / $previous_count) * 100);
    }
    
    function send_emails_about_outlier_harvests()
    {
        if(defined('SPG_EMAIL_ADDRESS') && defined('PLEARY_EMAIL_ADDRESS'))
        {
            if(abs($this->percent_different_data_objects()) > 10 ||
                abs($this->percent_different_hierarchy_entries()) > 10)
            {
                $subject = 'EOL Harvesting: outlier harvest event';
                $message = "A Harvest Event just completed which has an unusual change in data objects or taxa counts.\n\n";

                $message .= "ContentPartner: ". $this->resource->content_partner->full_name ."\n";
                $message .= "URL: http://eol.org/content_partners/". $this->resource->content_partner_id ."\n\n";

                $message .= "Resource: ". $this->resource->title ."\n";
                $message .= "URL: http://eol.org/content_partners/". $this->resource->content_partner_id ."/resources/$this->resource_id\n\n";

                $message .= "This Harvest Event Stats:\n";
                $message .= "Processed at: $this->completed_at\n";
                $message .= "Count of Objects: ". $this->count_data_objects() ." (a difference of ". round($this->percent_different_data_objects(), 2) ."%)\n";
                $message .= "Count of Taxa: ". $this->count_hierarchy_entries() ." (a difference of ". round($this->percent_different_hierarchy_entries(), 2) ."%)\n\n";

                $previous_harvest_event = $this->previous_harvest_event();
                $message .= "Previous Harvest Event Stats:\n";
                $message .= "Processed at: $previous_harvest_event->completed_at\n";
                $message .= "Count of Objects: ". $previous_harvest_event->count_data_objects() ."\n";
                $message .= "Count of Taxa: ". $previous_harvest_event->count_hierarchy_entries();


                $headers = 'From: no-reply@eol.org' . "\r\n" .
                    'Reply-To: no-reply@eol.org' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
                $to      = implode(", ", array(SPG_EMAIL_ADDRESS, PLEARY_EMAIL_ADDRESS));
                mail($to, $subject, $message, $headers);
            }
        }
    }
    
    private function add_objects_to_collection($collection)
    {
        $sound_type_ids = DataType::sound_type_ids();
        $image_type_ids = DataType::image_type_ids();
        $video_type_ids = DataType::video_type_ids();
        $map_type_ids = DataType::map_type_ids();
        $text_type_ids = DataType::text_type_ids();
        
        $query = "
            SELECT do.id, do.created_at, do.object_title, do.data_type_id
            FROM data_objects_harvest_events dohe
            JOIN data_objects do ON (dohe.data_object_id=do.id)
            WHERE dohe.harvest_event_id = $this->id";
        $used_ids = array();
        $count = 0;
        $outfile = temp_filepath();
        $OUT = fopen($outfile, 'w+');
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(isset($used_ids[$id])) continue;
            $created_at = $row[1];
            $object_title = trim($row[2]);
            $data_type_id = $row[3];
            if(in_array($data_type_id, $sound_type_ids)) $title = "Sound";
            elseif(in_array($data_type_id, $image_type_ids)) $title = "Image";
            elseif(in_array($data_type_id, $video_type_ids)) $title = "Video";
            elseif(in_array($data_type_id, $map_type_ids)) $title = "Map";
            elseif(in_array($data_type_id, $text_type_ids)) $title = "Text";
            else $title = "Data Object";
            
            fwrite($OUT, "NULL\t$title\tDataObject\t$id\t$collection->id\t$created_at\t$created_at\t\tNULL\n");
            $used_ids[$id] = true;
        }
        fclose($OUT);
        $this->mysqli->load_data_infile($outfile, 'collection_items');
        unlink($outfile);
        // echo "$dump_path\n";
    }
    
    private function add_taxa_to_collection($collection)
    {
        $query = "
            SELECT he.taxon_concept_id, he.created_at, n.string
            FROM harvest_events_hierarchy_entries hehe
            JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id)
            JOIN names n ON (he.name_id=n.id)
            WHERE hehe.harvest_event_id = $this->id";
        $used_ids = array();
        $count = 0;
        $outfile = temp_filepath();
        $OUT = fopen($outfile, 'w+');
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            if(isset($used_ids[$id])) continue;
            $created_at = $row[1];
            $name_string = trim($row[2]);
            fwrite($OUT, "NULL\t$name_string\tTaxonConcept\t$id\t$collection->id\t$created_at\t$created_at\t\tNULL\n");
            $used_ids[$id] = true;
        }
        fclose($OUT);
        $this->mysqli->load_data_infile($outfile, 'collection_items');
        unlink($outfile);
    }
}

?>
