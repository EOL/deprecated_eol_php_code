<?php

class DataObject extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public static function all()
    {
        $all = array();
        $result = $GLOBALS['db_connection']->query("SELECT * FROM data_objects");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new DataObject($row);
        }
        return $all;
    }
    
    public static function last()
    {
        $result = $GLOBALS['db_connection']->query("SELECT * FROM data_objects ORDER BY id DESC limit 1");
        if($result && $row=$result->fetch_assoc())
        {
            return new DataObject($row);
        }
        return null;
    }
    
    public static function delete($id)
    {
        if(!$id) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        
        $where_clause = "data_object_id=$id";
        if(is_array($id)) $where_clause = "data_object_id IN (".implode($id, ',').")";

        $mysqli->delete("DELETE FROM agents_data_objects WHERE $where_clause");
        $mysqli->delete("DELETE FROM data_objects_taxa WHERE $where_clause");
        $mysqli->delete("DELETE FROM data_objects_refs WHERE $where_clause");
        $mysqli->delete("DELETE FROM audiences_data_objects WHERE $where_clause");
        $mysqli->delete("DELETE FROM data_objects_info_items WHERE $where_clause");
        $mysqli->delete("DELETE FROM data_objects_table_of_contents WHERE $where_clause");
        $mysqli->delete("DELETE FROM data_objects_harvest_events WHERE $where_clause");
        
        
        $where_clause = "id=$id";
        if(is_array($id)) $where_clause = "id IN (".implode($id, ',').")";
        
        $mysqli->delete("DELETE FROM data_objects WHERE $where_clause");
        
        $mysqli->end_transaction();
    }
    
    public static function unpublish($id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->update("UPDATE data_objects SET published=0 WHERE id=$id");
    }
    
    public static function publish($id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->update("UPDATE data_objects SET published=1 WHERE id=$id");
    }
    
    public function unpublish_refs()
    {
        $this->mysqli->update("UPDATE data_objects do JOIN data_objects_refs dor ON (do.id=dor.data_object_id) JOIN refs r ON (dor.ref_id=r.id) SET r.published=0 WHERE do.guid='$this->guid'");
    }
    
    function getTaxa()
    {
        $taxa = array();
        
        $result = $this->mysqli->query("SELECT DISTINCT taxon_id FROM data_objects_taxa WHERE data_object_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            $taxa[] = new Taxon($row["taxon_id"]);
        }
        
        return $taxa;
    }
    
    public function add_reference($reference_id)
    {
        if(!$reference_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_refs VALUES ($this->id, $reference_id)");
    }
    
    public function add_agent($agent_id, $agent_role_id, $view_order)
    {
        if(!$agent_id) return false;
        if(!$agent_role_id) $agent_role_id = 0;
        $this->mysqli->insert("INSERT IGNORE INTO agents_data_objects VALUES ($this->id, $agent_id, $agent_role_id, $view_order)");
    }
    public function delete_agents()
    {
        $this->mysqli->insert("DELETE FROM agents_data_objects WHERE data_object_id=$this->id");
    }
    
    public function add_audience($audience_id)
    {
        if(!$audience_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO audiences_data_objects VALUES ($this->id, $audience_id)");
    }
    
    public function add_info_item($info_item_id)
    {
        if(!$info_item_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_info_items VALUES ($this->id, $info_item_id)");
    }
    
    public function references()
    {
        $references = array();
        
        $result = $this->mysqli->query("SELECT ref_id FROM data_objects_refs WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $references[] = new Reference($row["ref_id"]);
        }
        
        return $references;
    }
    
    public function agents()
    {
        $agents = array();
        
        $result = $this->mysqli->query("SELECT agent_id FROM agents_data_objects WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $agents[] = new Agent($row["agent_id"]);
        }
        
        return $agents;
    }
    
    public function info_items()
    {
        $info_items = array();
        $result = $this->mysqli->query("SELECT info_item_id FROM data_objects_info_items WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $info_items[] = new InfoItem($row["info_item_id"]);
        }
        return $info_items;
    }
    
    static function equivalent($data_object_1, $data_object_2)
    {
        $match = $data_object_1->equals($data_object_2);
        if($match) $match = $data_object_2->equals($data_object_1);
        
        return $match;
    }
    
    function equals($data_object)
    {
        $fields = $this->get_table_fields();
        foreach($fields as $field)
        {
            $fields_to_ignore = array("mysqli", "table_name", "id", "guid", "object_cache_url", "thumbnail_url", "thumbnail_cache_url", "object_created_at", "object_modified_at", "created_at", "updated_at", "data_rating", "vetted_id", "visibility_id", "curated", "published", "description_linked");
            if(in_array($field, $fields_to_ignore)) continue;
            
            if(@$this->$field == "0") $this->$field = 0;
            if(@$data_object->$field == "0") $data_object->$field = 0;
            if(isset($this->$field) && @$data_object->$field != $this->$field)
            {
                debug($data_object->$field." (<b>$field</b>) <b>DOES NOT EQUAL</b> ".$this->$field);
                return false;
            }
        }
        
        if(@$this->id)
        {
            if(!Functions::references_are_same($this->references(), $data_object->refs)) return false;
        }elseif(!Functions::references_are_same($this->refs, $data_object->references())) return false;
        
        return true;
    }
    
    function cache_object(&$content_manager, &$resource)
    {
        if($this->data_type_id==DataType::find("http://purl.org/dc/dcmitype/StillImage"))
        {
            if(preg_match("/^http:\/\//",$this->object_url))
            {
                // TODO - hardcoded exception to make the Biopix images smaller
                if($resource->title == "Biopix") $large_thumbnail_dimensions = "300x300";
                else $large_thumbnail_dimensions = CONTENT_IMAGE_LARGE;
                $this->object_cache_url = $content_manager->grab_file($this->object_url, 0, "content", $large_thumbnail_dimensions);
                if(@!$this->object_cache_url) return false;
            }else return false;
        }
        
        return true;
    }
    
    function cache_thumbnail(&$content_manager)
    {
        if($this->data_type_id!=DataType::find("http://purl.org/dc/dcmitype/StillImage") && $this->data_type_id!=DataType::find("http://purl.org/dc/dcmitype/Text"))
        {
            if(preg_match("/^http:\/\//",$this->thumbnail_url))
            {
                $this->thumbnail_cache_url = $content_manager->grab_file($this->thumbnail_url, 0, "content");
                if(@!$this->thumbnail_cache_url) return false;
            }else return false;
        }
        
        return true;
    }
    
    static function find_and_compare(&$resource, $data_object, &$content_manager)
    {
        if($data_object->data_type_id==DataType::find("http://purl.org/dc/dcmitype/Text") && @!trim($data_object->description)) return false;
        if($data_object->data_type_id==DataType::find("http://purl.org/dc/dcmitype/StillImage") && @!trim($data_object->object_url)) return false;
        if($data_object->data_type_id==DataType::find("http://purl.org/dc/dcmitype/Sound") && @!trim($data_object->object_url)) return false;
        if($data_object->data_type_id==DataType::find("http://purl.org/dc/dcmitype/MovingImage") && @!trim($data_object->object_url)) return false;
        
        $find_result = self::find($resource, $data_object);
        if(@!$find_result["exact"] && @!$find_result["similar"])
        {
            // Attempt to cache the object. Method will fail if the cache should have worked and it didn't
            if(!$data_object->cache_object($content_manager, $resource)) return false;
            
            $data_object->vetted_id = Vetted::insert('unknown');
            $data_object->visibility_id = Visibility::insert("Preview");
            
            return array(new DataObject(DataObject::insert($data_object)), "Inserted");
        }
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if($guid = $find_result["exact"])
        {
            // Checking to see if there is an object with the same guid in the LAST harvest event for the given resource -> UNCHANGED or UPDATED
            $result = $mysqli->query("SELECT SQL_NO_CACHE data_object_id, harvest_event_id FROM data_objects_harvest_events WHERE guid='$guid' ORDER BY harvest_event_id DESC, data_object_id DESC LIMIT 0,1");
            while($result && $row = $result->fetch_assoc())
            {
                $existing_data_object = new DataObject($row["data_object_id"]);
                
                if(self::equivalent($existing_data_object, $data_object))
                {
                    // This data object is equivalent (each field is identical) to the object in the last harvest with the same guid
                    // So we can reference the old object and don't need to create a new one
                    $status = "Unchanged";
                    if($row["harvest_event_id"] == $resource->harvest_event->id) $status = "Reused";
                    
                    return array($existing_data_object, "Unchanged");
                }else
                {
                    $data_object->vetted_id = Vetted::insert('unknown');
                    $data_object->visibility_id = Visibility::insert("Preview");
                    // This data object has different metadata than the object in the last harvest with the same guid
                    // So we have to create a new one with the same guid to reference for this harvest.
                    // The new one will inherit the curated, vetted, visibility info from the last object
                    $data_object->guid = $existing_data_object->guid;
                    $data_object->curated = $existing_data_object->curated;
                    if($resource->title != "Wikipedia")
                    {
                        // all new Wikipedia articles should be unvetted, even if the previous version was vetted
                        $data_object->vetted_id = $existing_data_object->vetted_id;
                    }
                    if($existing_data_object->visibility_id != Visibility::insert("Visible") && $existing_data_object->visibility_id != NULL)
                    {
                        // if the existing object is visible - this will go on as preview
                        // otherwise this will inherit the visibility (unpublished)
                        $data_object->visibility_id = $existing_data_object->visibility_id;
                    }
                    $data_object->data_rating = $existing_data_object->data_rating;
                    
                    // Check to see if we can reuse cached object or need to download it again
                    if($data_object->object_url == $existing_data_object->object_url && $existing_data_object->object_cache_url) $data_object->object_cache_url = $existing_data_object->object_cache_url;
                    elseif(!$data_object->cache_object($content_manager, $resource)) return false;
                    
                    // If the object is text and the contents have changed - set this version to curated = 0
                    if($data_object->data_type_id == DataType::insert("http://purl.org/dc/dcmitype/Text") && $existing_data_object->description != $data_object->description) $data_object->curated = 0;
                    
                    return array(new DataObject(DataObject::insert($data_object)), "Updated");
                }
            }
        }elseif($guids = $find_result["similar"])
        {
            // See if the metedata for this object is identical to previous similar objects -> REUSED or UPDATED
            $result = $mysqli->query("SELECT SQL_NO_CACHE data_object_id, harvest_event_id FROM data_objects_harvest_events WHERE guid IN ('".implode("','", $guids)."') ORDER BY harvest_event_id DESC, data_object_id DESC");
            while($result && $row = $result->fetch_assoc())
            {
                $existing_data_object = new DataObject($row["data_object_id"]);

                if(self::equivalent($existing_data_object, $data_object))
                {
                    $status = "Unchanged";
                    if($row["harvest_event_id"] == $resource->harvest_event->id) $status = "Reused";
                    
                    return array($existing_data_object, "Unchanged");
                }
            }
        }
        
        
        // I really don't think this should ever be reached. It was recently due to a bug,
        // so just in case - make sure to cache the object before returning that it was inserted
        
        if(!$data_object->cache_object($content_manager, $resource)) return false;
        $data_object->cache_thumbnail($content_manager);
        
        return array(new DataObject(DataObject::insert($data_object)), "Inserted");
    }
    
    static function insert($parameters)
    {
        if(get_class($parameters)=="DataObject")
        {
            $data_object = $parameters;
            if(@!$data_object->guid) $data_object->guid = Functions::generate_guid();
            return parent::insert_object_into($data_object, Functions::class_name(__FILE__));
        }
        
        if(@!$parameters["guid"]) $parameters["guid"] = Functions::generate_guid();
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($resource, $data_object)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $return = array("exact" => 0, "similar" => array());
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE do.guid FROM resources_taxa rt JOIN taxa t ON (rt.taxon_id=t.id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE resource_id=$resource->id AND dot.identifier!='' AND dot.identifier='".@$mysqli->escape($data_object->identifier)."' ORDER BY do.id DESC LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $return["exact"] = $row["guid"];
            return $return;
        }elseif(!$data_object->identifier)
        {
            //no identifier
            
            $query = "SELECT SQL_NO_CACHE DISTINCT do.guid FROM resources_taxa rt JOIN taxa t ON (rt.taxon_id=t.id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE resource_id=$resource->id AND ";
            
            $conditions = array();
            if(@$value = $data_object->data_type_id) $conditions[] = "do.data_type_id=$value";
            if(@$value = $data_object->mime_type_id) $conditions[] = "do.mime_type_id=$value";
            if(@$value = $data_object->object_url) $conditions[] = "do.object_url='".$mysqli->escape($value)."'";
            if($data_object->data_type_id==DataType::insert("http://purl.org/dc/dcmitype/Text") && @$value = $data_object->description) $conditions[] = "do.description='".$mysqli->escape($value)."'";
            
            $query .= implode(" AND ", $conditions);
            
            $guids = array();
            $result = $mysqli->query($query);
            while($result && $row=$result->fetch_assoc())
            {
                $guids[] = $row["guid"];
            }
            
            $return["similar"] = $guids;
            return $return;
        }
        
        return $return;
    }
}

?>