<?php

class Taxon_old extends MysqlBase
{
    static $name;
    
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
        
        $this->name = new Name($this->name_id);
    }
    
    public function add_reference($reference_id)
    {
        if(!$reference_id) return 0;
        $this->mysqli->insert("INSERT INTO refs_taxa VALUES ($this->id, $reference_id)");
    }
    
    public function add_common_name($common_name_id)
    {
        if(!$common_name_id) return 0;
        $this->mysqli->insert("INSERT INTO common_names_taxa VALUES ($this->id, $common_name_id)");
    }
    
    public function add_data_object($data_object_id, $identifier)
    {
        if(!$data_object_id) return 0;
        $identifier = $this->mysqli->escape($identifier);
        $this->mysqli->insert("INSERT INTO data_objects_taxa VALUES ($this->id, $data_object_id, '$identifier')");
    }
    
    public function common_names()
    {
      $common_names = array();
      
      $result = $this->mysqli->query("SELECT common_name_id FROM common_names_taxa WHERE taxon_id=$this->id");
      while($result && $row=$result->fetch_assoc())
      {
        $common_names[] = new CommonName($row["common_name_id"]);
      }
      
      return $common_names;
    }
    
    public function references()
    {
      $references = array();
      
      $result = $this->mysqli->query("SELECT ref_id FROM refs_taxa WHERE taxon_id=$this->id");
      while($result && $row=$result->fetch_assoc())
      {
        $references[] = new Reference($row["ref_id"]);
      }
      
      return $references;
    }
    
    
    
    static function equivalent($taxon, $t, $parameters)
    {
        $match = true;
        foreach($parameters as $k => $v)
        {
            if($taxon->$k != $v)
            {
                if($k == "name_id") continue;
                if($k == "taxon_created_at") continue;
                if($k == "taxon_modified_at") continue;
                if($k == "created_at") continue;
                if($k == "updated_at") continue;
                
                echo $taxon->$k." (($k)) DOES NOT EQUAL $v<br>";
                $match = false;
                break;
            }
        }
        
        if($match)
        {
            $vars = get_object_vars($taxon);
            foreach($vars as $k => $v)
            {
                if($k == "mysqli") continue;
                if($k == "table_name") continue;
                if($k == "name") continue;
                if($k == "id") continue;
                if($k == "guid") continue;
                if($k == "name_id") continue;
                if($k == "taxon_created_at") continue;
                if($k == "taxon_modified_at") continue;
                if($k == "created_at") continue;
                if($k == "updated_at") continue;
                
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
          if(Functions::common_names_are_same($taxon->common_names(), $t->commonNames) 
            && Functions::references_are_same($taxon->references(), $t->references))
          {
            
          }else $match = false;
        }
        
        // if($match)
        // {
        //   $common_names = $taxon->references();
        //   $old_common_names = array();
        //   foreach($common_names as $k) {
        //     $old_common_names[] = $k->common_name."|".$k->language->label;
        //   }
        //   $new_common_names = array();
        //   foreach($t->commonNames as $k)
        //   {
        //     $new_common_names[] = $k->name."|".$k->language;
        //   }
        //   if (array_diff($old_common_names, $new_common_names))
        //   {
        //     $match = false;
        //   }
        // }
        
        if($match) return true;
        return false;
    }
    
    static function find_and_compare($resource_id, $t)
    {
      $parameters = array();
      //$parameters["source_url"] = $t->source;
      $parameters["taxon_kingdom"] = $t->kingdom;
      $parameters["taxon_phylum"] = $t->phylum;
      $parameters["taxon_class"] = $t->class;
      $parameters["taxon_order"] = $t->order;
      $parameters["taxon_family"] = $t->family;
      $parameters["scientific_name"] = $t->scientificName;
      $parameters["name_id"] = Name::insert($t->scientificName);
      //$parameters["taxon_created_at"] = $t->created;
      //$parameters["taxon_modified_at"] = $t->modified;
      
        $guid = self::find($resource_id, $parameters, $t->identifier);
        if(!$guid) return array(new Taxon(Taxon::insert($parameters, $resource_id)), "Inserted");
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT taxon_id FROM harvest_events_taxa WHERE guid='$guid' ORDER BY harvest_event_id DESC LIMIT 0,1");
        if($result && $row = $result->fetch_assoc())
        {
            if(self::equivalent(new Taxon($row["taxon_id"]), $t, $parameters))
            {
                return array(new Taxon($row["taxon_id"]), "Unchanged");
            }else
            {
                $parameters["guid"] = $guid;
                $taxon_id = Taxon::insert($parameters, $resource_id);
                return array(new Taxon($taxon_id), "Updated");
            }
        }
        
        return array(new Taxon(Taxon::insert($parameters, $resource_id)), "Inserted");
    }
    
    static function insert($parameters, $resource_id)
    {
        //if($result = self::find($parameters)) return $result;
        
        $resource = new Resource($resource_id);
        $supplier_agent = $resource->data_supplier();
        
        if($supplier_agent && $hierarchy_id = Hierarchy::find_by_agent_id($supplier_agent->id))
        {
            $hierarchy = new Hierarchy($hierarchy_id);
        }else
        {
            $params = array();
            $params["agent_id"] = $supplier_agent->id;
            $params["description"] = "From resource ".$resource->id;
            $hierarchy_mock = Functions::mock_object("Hierarchy", $params);
            
            echo "Mock: $hierarchy_mock";
            
            $hierarchy = new Hierarchy(Hierarchy::insert($hierarchy_mock));
        }
        
        echo "Hier: $hierarchy";
        
        /*
            kingdom
            phylum
            class
            order
            family
            genus
            ScientificName
        */
        
        $name_ids = array();
        if(@$name = $parameters["taxon_kingdom"])   $name_ids[] = Name::insert($name);
        if(@$name = $parameters["taxon_phylum"])    $name_ids[] = Name::insert($name);
        if(@$name = $parameters["taxon_class"])     $name_ids[] = Name::insert($name);
        if(@$name = $parameters["taxon_order"])     $name_ids[] = Name::insert($name);
        if(@$name = $parameters["taxon_family"])    $name_ids[] = Name::insert($name);
        if(@$parameters["name_id"])                 $name_ids[] = $parameters["name_id"];
        
        $parent_hierarchy_entry = null;
        foreach($name_ids as $id)
        {
            $params = array();
            $params["name_id"] = $id;
            if($parent_hierarchy_entry)
            {
                if($parent_hierarchy_entry->ancestry) $params["ancestry"] = $parent_hierarchy_entry->ancestry."|".$parent_hierarchy_entry->id;
                else $params["ancestry"] = $parent_hierarchy_entry->id;
            }
            
            $params["hierarchy_id"] = $hierarchy->id;
            if($parent_hierarchy_entry) $params["parent_id"] = $parent_hierarchy_entry->id;
            if(@$parameters["identifier"]) $params["identifier"] = $parameters["identifier"];
            
            $mock_hierarchy_entry = Functions::mock_object("HierarchyEntry", $params);
            
            $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($mock_hierarchy_entry));
            $parent_hierarchy_entry = $hierarchy_entry;
        }
        
        if($parent_hierarchy_entry) $parameters["hierarchy_entry_id"] = $parent_hierarchy_entry->id;
        
        
        
        if(@!$parameters["guid"]) $parameters["guid"] = Functions::generate_guid();
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($resource_id, $parameters, $identifier)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT t.guid FROM resources_taxa rt JOIN taxa t ON (rt.taxon_id=t.id) WHERE resource_id=$resource_id AND rt.identifier!='' AND rt.identifier='".@$mysqli->escape($identifier)."'");
        if($result && $row=$result->fetch_assoc())
        {
            return $row["guid"];
        }elseif(!$identifier)
        {
            $query = "SELECT DISTINCT t.guid FROM resources_taxa rt JOIN taxa ON (rt.taxon_id=t.id) WHERE resource_id=$resource_id AND ";
            
            $conditions = array();
            if(@$value = $parameters["taxon_kingdom"]) $conditions[] = "t.taxon_kingdom='".$mysqli->escape($value)."'";
            if(@$value = $parameters["taxon_phylum"]) $conditions[] = "t.taxon_phylum='".$mysqli->escape($value)."'";
            if(@$value = $parameters["taxon_class"]) $conditions[] = "t.taxon_class='".$mysqli->escape($value)."'";
            if(@$value = $parameters["taxon_order"]) $conditions[] = "t.taxon_order='".$mysqli->escape($value)."'";
            if(@$value = $parameters["taxon_family"]) $conditions[] = "t.taxon_family='".$mysqli->escape($value)."'";
            if(@$value = $parameters["scientific_name"]) $conditions[] = "t.scientific_name='".$mysqli->escape($value)."'";
            
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