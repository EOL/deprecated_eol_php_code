<?php
namespace php_active_record;

class DataObject extends ActiveRecord
{
    public static $belongs_to = array(
            array('data_type'),
            array('data_subtype', 'class_name' => 'DataType', 'foreign_key' => 'data_subtype_id'),
            array('mime_type'),
            array('language'),
            array('license'),
            array('vetted'),
            array('visibility')
        );

    public static $has_many = array(
            array('audiences_data_objects'),
            array('audiences', 'through' => 'audiences_data_objects'),
            array('data_objects_info_items'),
            array('info_items', 'through' => 'data_objects_info_items'),
            array('data_objects_refs'),
            array('references', 'through' => 'data_objects_refs'),
            array('agents_data_objects'),
            array('agents', 'through' => 'agents_data_objects'),
            array('data_objects_hierarchy_entries'),
            array('hierarchy_entries', 'through' => 'data_objects_hierarchy_entries'),
            array('data_objects_taxon_concepts'),
            array('taxon_concepts', 'through' => 'data_objects_taxon_concepts'),
        );

    public static function delete($id)
    {
        if(!$id) return false;

        $GLOBALS['mysqli_connection']->begin_transaction();

        $where_clause = "data_object_id=$id";
        if(is_array($id)) $where_clause = "data_object_id IN (".implode($id, ',').")";

        $GLOBALS['mysqli_connection']->delete("DELETE FROM agents_data_objects WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM data_objects_hierarchy_entries WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM data_objects_refs WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM audiences_data_objects WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM data_objects_info_items WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM data_objects_table_of_contents WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM data_objects_harvest_events WHERE $where_clause");
        $GLOBALS['mysqli_connection']->delete("DELETE FROM image_sizes WHERE $where_clause");


        $where_clause = "id=$id";
        if(is_array($id)) $where_clause = "id IN (".implode($id, ',').")";

        $GLOBALS['mysqli_connection']->delete("DELETE FROM data_objects WHERE $where_clause");

        $GLOBALS['mysqli_connection']->end_transaction();
    }

    public static function unpublish($id)
    {
        $GLOBALS['mysqli_connection']->update("UPDATE data_objects SET published=0 WHERE id=$id");
    }

    public static function publish($id)
    {
        $GLOBALS['mysqli_connection']->update("UPDATE data_objects SET published=1 WHERE id=$id");
    }

    public function delete_refs()
    {
        $result = $this->mysqli->query("SELECT * FROM data_objects_refs WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->delete("DELETE FROM data_objects_refs WHERE data_object_id=". $row['data_object_id'] ." AND ref_id=". $row['ref_id']);
        }
    }

    public function add_reference($reference_id)
    {
        if(!$reference_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_refs VALUES ($this->id, $reference_id)");
    }

    public function published_references()
    {
        $published_refs = array();
        foreach($this->references as $ref)
        {
            if($ref->published) $published_refs[] = $ref;
        }
        return $published_refs;
    }

    public function add_agent($agent_id, $agent_role_id, $view_order)
    {
        if(!$agent_id) return false;
        if(!$agent_role_id) $agent_role_id = 0;
        $this->mysqli->insert("INSERT IGNORE INTO agents_data_objects VALUES ($this->id, $agent_id, $agent_role_id, $view_order)");
    }
    public function delete_agents()
    {
        $result = $this->mysqli->query("SELECT * FROM agents_data_objects WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->delete("DELETE FROM agents_data_objects WHERE data_object_id=". $row['data_object_id'] ." AND agent_id=". $row['agent_id'] ." AND agent_role_id=". $row['agent_role_id']);
        }
    }

    public function add_translation($data_object_id, $language_id)
    {
        if(!$data_object_id) return false;
        if(!$language_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO data_object_translations VALUES (NULL, $this->id, $data_object_id, $language_id, NOW(), NOW())");
    }
    public function delete_translations()
    {
        $this->mysqli->insert("DELETE FROM data_object_translations WHERE data_object_id=$this->id");
    }


    public function delete_audiences()
    {
        $this->mysqli->insert("DELETE FROM audiences_data_objects WHERE data_object_id=$this->id");
    }
    public function add_audience($audience_id)
    {
        if(!$audience_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO audiences_data_objects VALUES ($this->id, $audience_id)");
    }

    public function delete_info_items()
    {
        $result = $this->mysqli->query("SELECT * FROM data_objects_info_items WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->delete("DELETE FROM data_objects_info_items WHERE data_object_id=". $row['data_object_id'] ." AND info_item_id=". $row['info_item_id']);
        }
    }
    public function add_info_item($info_item_id)
    {
        if(!$info_item_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_info_items VALUES ($this->id, $info_item_id)");
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_table_of_contents (SELECT $this->id, toc_id FROM info_items WHERE id=$info_item_id AND toc_id!=0)");
    }

    public function delete_table_of_contents()
    {
        $result = $this->mysqli->query("SELECT * FROM data_objects_table_of_contents WHERE data_object_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->delete("DELETE FROM data_objects_table_of_contents WHERE data_object_id=". $row['data_object_id'] ." AND toc_id=". $row['toc_id']);
        }
    }
    public function add_table_of_contents($toc_id)
    {
        if(!$toc_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_table_of_contents ($this->id, $toc_id)");
    }




    public function delete_hierarchy_entries()
    {
        $this->mysqli->insert("DELETE FROM data_objects_hierarchy_entries WHERE data_object_id=$this->id");
    }

    static function equivalent($data_object_1, $data_object_2)
    {
        $match = $data_object_1->equals($data_object_2);
        if($match) $match = $data_object_2->equals($data_object_1);

        return $match;
    }

    function equals($data_object)
    {
        $fields = self::table_fields();
        foreach($fields as $field)
        {
            $fields_to_ignore = array("mysqli", "table_name", "id", "guid", "object_cache_url", "thumbnail_url", "thumbnail_cache_url",
                "object_created_at", "object_modified_at", "created_at", "updated_at", "data_rating", "vetted_id",
                "visibility_id", "curated", "published", "description_linked", "available_at", "additional_information");
            if(in_array($field, $fields_to_ignore)) continue;

            if(@$this->$field == "0") $this->$field = 0;
            if(@$data_object->$field == "0") $data_object->$field = 0;
            if(isset($this->$field) && @$data_object->$field != $this->$field)
            {
                if(($field == 'longitude' || $field == 'latitude') && @abs($data_object->$field - $this->$field) < 1) continue;
                debug("Data Object " . $data_object->id . " changed: " .
                    $data_object->$field . " (<b>$field</b>) -> " .
                    $this->$field);
                return false;
            }
        }

        // if(@$this->id)
        // {
        //     if(!Functions::references_are_same($this->references(), $data_object->refs)) return false;
        // }elseif(!Functions::references_are_same($this->refs, $data_object->references())) return false;

        return true;
    }

    function cache_object(&$content_manager, &$resource)
    {
        if($this->is_image())
        {
            if(preg_match("/^http:\/\//",$this->object_url) || preg_match("/^https:\/\//",$this->object_url))
            {
                // Hardcoded exception to make the Biopix images smaller
                $image_options = array('data_object_id' => $this->id, 'data_object_guid' => $this->guid);
                if($resource->title == "Biopix") $image_options['large_image_dimensions'] = array(300, 300);
                if(isset($this->additional_information) && isset($this->additional_information['rotation']))
                {
                    $image_options['rotation'] = $this->additional_information['rotation'];
                }
                $this->object_cache_url = $content_manager->grab_file($this->object_url, "image", $image_options);
                if(@!$this->object_cache_url) return false;
            }else return false;
        }
        if($this->is_video() && $this->data_type_id != DataType::youtube()->id)
        {
            if(preg_match("/^http:\/\//",$this->object_url))
            {
                $this->object_cache_url = $content_manager->grab_file($this->object_url, "video");
                if(@!$this->object_cache_url) return false;
            }else return false;
        }
        if($this->is_sound())
        {
            if(preg_match("/^http:\/\//",$this->object_url))
            {
                $this->object_cache_url = $content_manager->grab_file($this->object_url, "audio");
                if(@!$this->object_cache_url) return false;
            }else return false;
        }
        return true;
    }

    function cache_thumbnail(&$content_manager)
    {
        if($this->is_video() || $this->is_sound() || $this->is_flash() || $this->is_youtube())
        {
            if(preg_match("/^http:\/\//",$this->thumbnail_url))
            {
                $this->thumbnail_cache_url = $content_manager->grab_file($this->thumbnail_url, "image");
                if(@!$this->thumbnail_cache_url) return false;
            }else return false;
        }
        return true;
    }

    static function find_and_compare(&$resource, $data_object, &$content_manager)
    {
        if(!$data_object->data_type) return false;
        if($data_object->is_text() && @!trim($data_object->description)) return false;
        if($data_object->is_image() && @!trim($data_object->object_url)) return false;
        if($data_object->is_sound() && @!trim($data_object->object_url)) return false;
        if($data_object->is_video() && @!trim($data_object->object_url)) return false;

        $find_result = self::find_in_resource($resource, $data_object);
        if($guid = $find_result["exact"])
        {
            // Checking to see if there is an object with the same guid in the LAST harvest event for the given resource -> UNCHANGED or UPDATED
            $result = $GLOBALS['mysqli_connection']->query("SELECT SQL_NO_CACHE dohe.data_object_id, dohe.harvest_event_id FROM data_objects_harvest_events dohe JOIN harvest_events he ON (dohe.harvest_event_id=he.id) WHERE dohe.guid='$guid' AND he.resource_id=$resource->id ORDER BY harvest_event_id DESC, data_object_id DESC LIMIT 0,1");
            while($result && $row = $result->fetch_assoc())
            {
                $existing_data_object = DataObject::find($row["data_object_id"]);

                if(self::equivalent($existing_data_object, $data_object))
                {
                    // This data object is equivalent (each field is identical) to the object in the last harvest with the same guid
                    // So we can reference the old object and don't need to create a new one
                    $status = "Unchanged";
                    if($row["harvest_event_id"] == @$resource->harvest_event->id) $status = "Reused";

                    return array($existing_data_object, $status , $existing_data_object);
                }else
                {
                    // This data object has different metadata than the object in the last harvest with the same guid
                    // So we have to create a new one with the same guid to reference for this harvest.
                    // The new one will inherit the curated, vetted, visibility info from the last object
                    $data_object->guid = $existing_data_object->guid;
                    $data_object->curated = $existing_data_object->curated;
                    $data_object->data_rating = $existing_data_object->data_rating;

                    // Check to see if we can reuse cached object or need to download it again
                    if(strtolower($data_object->object_url) == strtolower($existing_data_object->object_url) && $existing_data_object->object_cache_url) $data_object->object_cache_url = $existing_data_object->object_cache_url;
                    elseif(!$data_object->cache_object($content_manager, $resource)) return false;

                    if(!$data_object->thumbnail_cache_url) $data_object->cache_thumbnail($content_manager);
                    // If the object is text and the contents have changed - set this version to curated = 0
                    if($data_object->is_text() && $existing_data_object->description != $data_object->description) $data_object->curated = 0;

                    $new_data_object =
                        DataObject::create_by_object($data_object);
                    $GLOBALS['mysqli_connection']->query(
                        "UPDATE taxon_concept_exemplar_images
                         SET data_object_id=$new_data_object->id
                         WHERE data_object_id=$existing_data_object->id");
                    $GLOBALS['mysqli_connection']->query(
                        "UPDATE taxon_concept_exemplar_articles
                         SET data_object_id=$new_data_object->id
                         WHERE data_object_id=$existing_data_object->id");
                    //if the old image was cropped, crop the new image
                    $result = $GLOBALS['mysqli_connection']->query("SELECT SQL_NO_CACHE id, height, width, crop_x_pct, crop_y_pct, crop_width_pct, crop_height_pct FROM image_sizes WHERE data_object_id=$existing_data_object->id ORDER BY id DESC LIMIT 1");
                    if ($result && $row = $result->fetch_assoc()){
                        $GLOBALS['mysqli_connection']->insert("INSERT INTO image_sizes (data_object_id, height, width, crop_x_pct, crop_y_pct, crop_width_pct, crop_height_pct) VALUES (" .
                            $new_data_object->id . " , " .
                            $row['height'] . " , " .
                            $row['width'] . " , " .
                            $row['crop_x_pct'] . " , " .
                            $row['crop_y_pct'] . " , " .
                            $row['crop_width_pct'] . " , " .
                            $row['crop_height_pct'] . " )");
                    }
                    return array($new_data_object, "Updated",
                        $existing_data_object);
                }
            }
        }elseif($guids = $find_result["similar"])
        {
            // See if the metedata for this object is identical to previous similar objects -> REUSED or UPDATED
            $result = $GLOBALS['mysqli_connection']->query("SELECT SQL_NO_CACHE data_object_id, harvest_event_id FROM data_objects_harvest_events dohe JOIN harvest_events he ON (dohe.harvest_event_id=he.id) WHERE guid IN ('".implode("','", $guids)."') AND he.resource_id=$resource->id ORDER BY harvest_event_id DESC, data_object_id DESC");
            while($result && $row = $result->fetch_assoc())
            {
                $existing_data_object = DataObject::find($row["data_object_id"]);

                if(self::equivalent($existing_data_object, $data_object))
                {
                    $status = "Unchanged";
                    if($row["harvest_event_id"] == $resource->harvest_event->id) $status = "Reused";

                    return array($existing_data_object, $status , $existing_data_object);
                }
            }
        }


        // Will get here if the object is similar to an existing object (description the same, image URL the same)
        // but there is a difference - eg the identifiers are different. Or if the object is entirely new

        // // Attempt to cache the object. Method will fail if the cache should have worked and it didn't
        if(!$data_object->cache_object($content_manager, $resource)) return false;
        $data_object->cache_thumbnail($content_manager);

        return array(DataObject::create_by_object($data_object), "Inserted", null);
    }

    static function create_by_object($data_object)
    {
        if(@!$data_object->guid) $data_object->guid = Functions::generate_guid();

        return self::create($data_object);
    }

    static function find_in_resource($resource, $data_object)
    {
        $return = array("exact" => 0, "similar" => array());

        if(!$data_object->identifier)
        {
            $query = "SELECT SQL_NO_CACHE DISTINCT do.guid FROM harvest_events he JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE he.resource_id=$resource->id AND ";

            $conditions = array();
            if($data_object->data_type)
            {
                $conditions[] = "do.data_type_id=".$data_object->data_type->id;
                if($data_object->is_text() && $data_object->description)
                {
                    $conditions[] = "do.description='".$GLOBALS['mysqli_connection']->escape($data_object->description)."'";
                }
            }
            if($data_object->mime_type) $conditions[] = "do.mime_type_id=".$data_object->mime_type->id;
            if($data_object->object_url) $conditions[] = "do.object_url='".$GLOBALS['mysqli_connection']->escape($data_object->object_url)."'";

            $query .= implode(" AND ", $conditions);

            $guids = array();
            $result = $GLOBALS['mysqli_connection']->query($query);
            while($result && $row=$result->fetch_assoc())
            {
                $guids[] = $row["guid"];
            }

            $return["similar"] = $guids;
            return $return;
        }else
        {
            $identifier = @$GLOBALS['mysqli_connection']->escape($data_object->identifier);
            $result = $GLOBALS['mysqli_connection']->query("SELECT SQL_NO_CACHE do.guid FROM harvest_events he JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE he.resource_id=$resource->id AND do.identifier!='' AND do.identifier='$identifier' ORDER BY do.id DESC LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                $return["exact"] = $row["guid"];
                return $return;
            }
        }

        return $return;
    }

    public function is_image()
    {
        if($this->data_type_id && $this->data_type_id == DataType::image()->id) return true;
        return false;
    }

    public function is_text()
    {
        if($this->data_type_id && $this->data_type_id == DataType::text()->id) return true;
        return false;
    }

    public function is_video()
    {
        if($this->data_type_id && $this->data_type_id == DataType::video()->id) return true;
        return false;
    }

    public function is_sound()
    {
        if($this->data_type_id && $this->data_type_id == DataType::sound()->id) return true;
        return false;
    }

    public function is_iucn()
    {
        if($this->data_type_id && $this->data_type_id == DataType::iucn()->id) return true;
        return false;
    }

    public function is_flash()
    {
        if($this->data_type_id && $this->data_type_id == DataType::flash()->id) return true;
        return false;
    }

    public function is_youtube()
    {
        if($this->data_type_id && $this->data_type_id == DataType::youtube()->id) return true;
        return false;
    }

    public function best_vetted()
    {
        $weights = array();
        $weights[Vetted::inappropriate()->id] = 1;
        $weights[Vetted::untrusted()->id] = 2;
        $weights[Vetted::unknown()->id] = 3;
        $weights[Vetted::trusted()->id] = 4;
        $best_vetted = null;
        if($hes = $this->data_objects_hierarchy_entries)
        {
            foreach($hes as $he)
            {
                if($he->vetted && (!$best_vetted || $weights[$he->vetted->id] > $weights[$best_vetted->id]))
                {
                    $best_vetted = $he->vetted;
                }
            }
        }
        return $best_vetted;
    }

    public function best_visibility()
    {
        $weights = array();
        $weights[Visibility::preview()->id] = 1;
        $weights[Visibility::invisible()->id] = 2;
        $weights[Visibility::visible()->id] = 3;
        $best_visibility = null;
        if($hes = $this->data_objects_hierarchy_entries)
        {
            foreach($hes as $he)
            {
                if($he->visibility && (!$best_visibility || $weights[$he->visibility->id] > $weights[$best_visibility->id]))
                {
                    $best_visibility = $he->visibility;
                }
            }
        }
        return $best_visibility;
    }

}

?>
