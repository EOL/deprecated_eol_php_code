<?php

class DataObject_old extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
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
        $this->mysqli->insert("INSERT INTO data_objects_refs VALUES ($this->id, $reference_id)");
    }
    
    public function add_agent($agent_id, $role, $view_order)
    {
        if(!$agent_id) return 0;
        $this->mysqli->insert("INSERT INTO agents_data_objects VALUES ($this->id, $agent_id, ".AgentRole::insert($role).", $view_order)");
    }
    
    public function add_audience($audience)
    {
        $this->mysqli->insert("INSERT INTO audiences_data_objects VALUES ($this->id, ".Audience::insert($audience).")");
    }
    
    public function add_info_item($info_item)
    {
        $this->mysqli->insert("INSERT INTO data_objects_info_items VALUES ($this->id, ".InfoItem::insert($info_item).")");
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
    
    static function equivalent($data_object, $dataObject, $parameters)
    {
        $match = true;
        foreach($parameters as $k => $v)
        {
            if(@$data_object->$k != $v)
            {
                if($k == "object_cache_url") continue;
                if($k == "thumbnail_url") continue;
                if($k == "thumbnail_cache_url") continue;
                if($k == "object_created_at") continue;
                if($k == "object_modified_at") continue;
                if($k == "created_at") continue;
                if($k == "updated_at") continue;
                if($k == "data_rating") continue;
                if($k == "vetted") continue;
                if($k == "visible") continue;
                
                echo $data_object->$k." ($k) DOES NOT EQUAL $v<br>";
                $match = false;
                break;
            }
        }
        
        if($match)
        {
            $vars = get_object_vars($data_object);
            foreach($vars as $k => $v)
            {
                if($k == "mysqli") continue;
                if($k == "table_name") continue;
                if($k == "id") continue;
                if($k == "guid") continue;
                if($k == "object_cache_url") continue;
                if($k == "thumbnail_url") continue;
                if($k == "thumbnail_cache_url") continue;
                if($k == "object_created_at") continue;
                if($k == "object_modified_at") continue;
                if($k == "created_at") continue;
                if($k == "updated_at") continue;
                if($k == "data_rating") continue;
                if($k == "vetted") continue;
                if($k == "visible") continue;
                
                if(@$parameters[$k] != $v)
                {
                    echo $parameters[$k]." ($k) DOES NOT EQUAL $v<br>";
                    $match = false;
                    break;
                }
            }
        }
        
        if($match)
        {
          if(Functions::references_are_same($data_object->references(), $dataObject->references)
            && Functions::agents_are_same($data_object->agents(), $dataObject->agents))
          {
            
          }else $match = false;
        }
        
        if($match) return true;
        return false;
    }
    
    static function find_and_compare($resource_id, $dataObject, &$content_manager)
    {
      $parameters = array();
      $parameters["data_type_id"] = DataType::insert($dataObject->dataType);
      $parameters["mime_type_id"] = MimeType::insert($dataObject->mimeType);
      $parameters["object_title"] = $dataObject->title;
      $parameters["language_id"] = Language::insert($dataObject->language);
      $parameters["license_id"] = License::insert($dataObject->license);
      $parameters["rights_statement"] = $dataObject->rights;
      $parameters["rights_holder"] = $dataObject->rightsHolder;
      $parameters["bibliographic_citation"] = $dataObject->bibliographicCitation;
      $parameters["source_url"] = $dataObject->source;
      $parameters["description"] = $dataObject->description;
      $parameters["object_url"] = $dataObject->mediaURL;
      $parameters["thumbnail_url"] = $dataObject->thumbnailURL;
      $parameters["location"] = $dataObject->location;
      $parameters["object_created_at"] = $dataObject->created;
      $parameters["object_modified_at"] = $dataObject->modified;
      //preview status
      $parameters["visibility_id"] = 2;
       
      $parameters["latitude"] = 0;
      $parameters["longitude"] = 0;
      $parameters["altitude"] = 0;
      if($dataObject->point)
      {
          $parameters["latitude"] = $dataObject->point->latitude;
          $parameters["longitude"] = $dataObject->point->longitude;
          $parameters["altitude"] = $dataObject->point->altitude;
      }
      
        $guid = self::find($resource_id, $parameters, $dataObject->identifier);
        if(!$guid)
        {
          if($parameters["data_type_id"]==DataType::find("http://purl.org/dc/dcmitype/StillImage") && preg_match("/^http:\/\//",$dataObject->mediaURL))
          {
              //$parameters["object_cache_url"] = $content_manager->grab_file($dataObject->mediaURL,0,"content");
          }
          
          if($parameters["data_type_id"]==DataType::find("http://purl.org/dc/dcmitype/StillImage") && preg_match("/^http:\/\//",$dataObject->thumbnailURL))
          {
              //$parameters["thumbnail_cache_url"] = $content_manager->grab_file($dataObject->thumbnailURL,0,"content");
          }
          
          return array(new DataObject(DataObject::insert($parameters)), "Inserted");
        }
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT data_object_id FROM data_objects_harvest_events WHERE guid='$guid' ORDER BY harvest_event_id DESC LIMIT 0,1");
        if($result && $row = $result->fetch_assoc())
        {
            if(self::equivalent(new DataObject($row["data_object_id"]), $dataObject, $parameters))
            {
                return array(new DataObject($row["data_object_id"]), "Unchanged");
            }else
            {
                $parameters["guid"] = $guid;
                $data_object_id = DataObject::insert($parameters);
                return array(new DataObject($data_object_id), "Updated");
            }
        }
        
        return array(new DataObject(DataObject::insert($parameters)), "Inserted");
    }
    
    static function insert($parameters)
    {
        //if($result = self::find($parameters)) return $result;
        
        if(@!$parameters["guid"]) $parameters["guid"] = Functions::generate_guid();
        if(@!$parameters["data_rating"]) $parameters["data_rating"] = 9999;
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($resource_id, $parameters, $identifier)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT do.guid FROM resources_taxa rt JOIN taxa t ON (rt.taxon_id=t.id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE resource_id=$resource_id AND dot.identifier!='' AND dot.identifier='".@$mysqli->escape($identifier)."'");
        if($result && $row=$result->fetch_assoc())
        {
            return $row["guid"];
        }elseif(!$identifier)
        {
            //no identifier
            
            $query = "SELECT DISTINCT do.guid FROM resources_taxa rt JOIN taxa t ON (rt.taxon_id=t.id) taxa t JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) JOIN data_objects do ON (dot.data_object_id=do.id) WHERE resource_id=$resource_id AND ";
            
            $conditions = array();
            if(@$value = $parameters["data_type_id"]) $conditions[] = "data_type_id=$value";
            if(@$value = $parameters["mime_type_id"]) $conditions[] = "mime_type_id=$value";
            if(@$value = $parameters["object_url"]) $conditions[] = "do.object_url='".$mysqli->escape($value)."'";
            if($parameters["data_type_id"]==DataType::insert("http://purl.org/dc/dcmitype/Text") && @$value = $parameters["description"]) $conditions[] = "description='".$mysqli->escape($value)."'";
            
            $query .= implode(" AND ", $conditions);
            
            $result = $mysqli->query($query);
            if($result && $result->num_rows > 1)
            {
                //we have some problem - more than one matching data object
            }elseif($result && $result->num_rows == 1)
            {
                $row = $result->fetch_assoc();
                return $row["guid"];
            }
        }
        
        return 0;
    }
}

?>