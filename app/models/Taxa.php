<?php

class Taxon extends MysqlBase
{
    static $name;
    
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
        $result = $mysqli->query("SELECT * FROM taxa");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new Taxon($row);
        }
        return $all;
    }
    
    public function name()
    {
        if(@$this->name) return $this->name;
        
        $this->name = new Name($this->name_id);
        return $this->name;
    }
    
    public function hierarchy_entry()
    {
        if(@$this->hierarchy_entry) return $this->hierarchy_entry;
        
        $this->hierarchy_entry = new HierarchyEntry($this->hierarchy_entry_id);
        return $this->hierarchy_entry;
    }
    
    public function unpublish_refs()
    {
        $this->mysqli->update("UPDATE taxa t JOIN refs_taxa rt ON (t.id=rt.taxon_id) JOIN refs r ON (rt.ref_id=r.id) SET r.published=0 WHERE t.hierarchy_entry_id=$this->hierarchy_entry_id");
    }
    
    public function add_reference($reference_id)
    {
        if(!$reference_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO refs_taxa VALUES ($this->id, $reference_id)");
    }
    
    public function add_common_name($c)
    {
        $common_name_id = CommonName::insert($c);
        if(!$common_name_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO common_names_taxa VALUES ($this->id, $common_name_id)");
        
        $name_id = Name::insert($c->common_name);
        if(!$c->language_id) $c->language_id = Language::insert('Common name');
        //Name::add_language_by_name_id($name_id, $c->language_id, $this->name_id, 0);
    }
    
    public function add_data_object($data_object_id, $data_object)
    {
        if(!$data_object_id) return 0;
        $identifier = @$this->mysqli->escape($data_object->identifier);
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_taxa VALUES ($this->id, $data_object_id, '$identifier')");
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
    
    
    
    static function equivalent($taxon_1, $taxon_2)
    {
        $match = $taxon_1->equals($taxon_2);
        if($match) $match = $taxon_2->equals($taxon_1);
        
        return $match;
    }
    
    function equals($taxon)
    {
        $fields = $this->get_table_fields();
        foreach($fields as $field)
        {
            $fields_to_ignore = array("id", "guid", "name_id", "hierarchy_entry_id", "created_at", "updated_at");
            if(in_array($field, $fields_to_ignore)) continue;
            
            if(isset($this->$field) && $taxon->$field != $this->$field)
            {
                debug($taxon->$field." (<b>$field</b>) <b>DOES NOT EQUAL</b> ".$this->$field);
                return false;
            }
        }
        
        if(@$this->id)
        {
            if(@!Functions::common_names_are_same($this->common_names(), $taxon->common_names)) return false;
        }elseif(@!Functions::common_names_are_same($this->common_names, $taxon->common_names())) return false;
        
        if(@$this->id)
        {
            if(@!Functions::references_are_same($this->references(), $taxon->refs)) return false;
        }elseif(@!Functions::references_are_same($this->refs, $taxon->references())) return false;
        
        return true;
    }
    
    static function find_and_compare(&$resource, $taxon)
    {
        $find_result = self::find($resource->id, $taxon);
        if(@!$find_result["exact"] && @!$find_result["similar"])
        {
            return array(new Taxon(Taxon::insert($taxon, $resource->id)), "Inserted");
        }
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if($guid = $find_result["exact"])
        {
            $result = $mysqli->query("SELECT SQL_NO_CACHE taxon_id FROM harvest_events_taxa WHERE guid='$guid' AND harvest_event_id=".$resource->harvest_event->id." ORDER BY taxon_id DESC LIMIT 0,1");
            if($result && $row=$result->fetch_assoc())
            {
                return array(new Taxon($row["taxon_id"]), "Reused");
            }

            $result = $mysqli->query("SELECT SQL_NO_CACHE taxon_id, harvest_event_id, guid FROM harvest_events_taxa WHERE guid='$guid' AND harvest_event_id=".($resource->last_harvest_event_id())." ORDER BY harvest_event_id, taxon_id DESC LIMIT 0,1");
            if($result && $row=$result->fetch_assoc())
            {
                if(self::equivalent(new Taxon($row["taxon_id"]), $taxon))
                {
                    return array(new Taxon($row["taxon_id"]), "Unchanged");
                }else
                {
                    $taxon->guid = $guid;
                    $taxon_id = Taxon::insert($taxon, $resource->id);
                    return array(new Taxon($taxon_id), "Updated");
                }
            }
        }elseif($guids = $find_result["similar"])
        {
            $result = $mysqli->query("SELECT SQL_NO_CACHE taxon_id FROM harvest_events_taxa WHERE guid IN ('".implode("','", $guids)."') AND harvest_event_id=".$resource->harvest_event->id);
            if($result && $result->num_rows !=0 )
            {
                while($result && $row=$result->fetch_assoc())
                {
                    if(self::equivalent(new Taxon($row["taxon_id"]), $taxon))
                    {
                        return array(new Taxon($row["taxon_id"]), "Reused");
                    }
                }
            }else
            {
                $result = $mysqli->query("SELECT SQL_NO_CACHE taxon_id, harvest_event_id, guid FROM harvest_events_taxa WHERE guid IN ('".implode("','", $guids)."') AND harvest_event_id=".($resource->last_harvest_event_id())." ORDER BY harvest_event_id, taxon_id DESC");
                while($result && $row=$result->fetch_assoc())
                {
                    if(self::equivalent(new Taxon($row["taxon_id"]), $taxon))
                    {
                        return array(new Taxon($row["taxon_id"]), "Unchanged");
                    }
                }
            }
        }
        
        return array(new Taxon(Taxon::insert($taxon, $resource->id)), "Inserted");
    }
    
    static function insert($parameters, $resource_id)
    {
        if(get_class($parameters)=="Taxon")
        {
            $taxon = $parameters;
            
            $hierarchy_entry_id = self::add_to_hierarchy_entries($taxon, $resource_id);
            if($hierarchy_entry_id) $taxon->hierarchy_entry_id = $hierarchy_entry_id;
            
            if(@!$taxon->guid) $taxon->guid = Functions::generate_guid();
            return parent::insert_object_into($taxon, Functions::class_name(__FILE__));
        }
        
        if(@!$parameters["guid"]) $parameters["guid"] = Functions::generate_guid();
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($resource_id, $taxon)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $return = array("exact" => 0, "similar" => array());
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE t.guid FROM resources_taxa rt JOIN taxa t ON (rt.taxon_id=t.id) WHERE resource_id=$resource_id AND rt.identifier!='' AND rt.identifier='".@$mysqli->escape($taxon->identifier)."'");
        if($result && $row=$result->fetch_assoc())
        {
            $return["exact"] = $row["guid"];
            return $return;
        }elseif(@!$taxon->identifier)
        {
            $query = "SELECT SQL_NO_CACHE DISTINCT t.guid FROM taxa t WHERE name_id=$taxon->name_id AND ";
            
            $conditions = array();
            if(@$value = $taxon->taxon_kingdom) $conditions[] = "t.taxon_kingdom='".$mysqli->escape($value)."'";
            else $conditions[] = "t.taxon_kingdom=''";
            if(@$value = $taxon->taxon_phylum) $conditions[] = "t.taxon_phylum='".$mysqli->escape($value)."'";
            else $conditions[] = "t.taxon_phylum=''";
            if(@$value = $taxon->taxon_class) $conditions[] = "t.taxon_class='".$mysqli->escape($value)."'";
            else $conditions[] = "t.taxon_class=''";
            if(@$value = $taxon->taxon_order) $conditions[] = "t.taxon_order='".$mysqli->escape($value)."'";
            else $conditions[] = "t.taxon_order=''";
            if(@$value = $taxon->taxon_family) $conditions[] = "t.taxon_family='".$mysqli->escape($value)."'";
            else $conditions[] = "t.taxon_family=''";
            // if(@$value = $taxon->taxon_genus) $conditions[] = "t.taxon_genus='".$mysqli->escape($value)."'";
            // else $conditions[] = "t.taxon_genus=''";
            if(@$value = $taxon->scientific_name) $conditions[] = "t.scientific_name='".$mysqli->escape($value)."'";
            else $conditions[] = "t.scientific_name=''";
            
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
    
    static function add_to_hierarchy_entries($taxon, $resource_id)
    {
        //if($result = self::find($parameters)) return $result;
        
        $resource = new Resource($resource_id);
        $hierarchy = new Hierarchy($resource->insert_hierarchy());
        
        $name_ids = array();
        if(@$string = $taxon->taxon_kingdom)
        {
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["kingdom"] = $name->id;
        }
        if(@$string = $taxon->taxon_phylum)
        {
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["phylum"] = $name->id;
        }
        if(@$string = $taxon->taxon_class)
        {
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["class"] = $name->id;
        }
        if(@$string = $taxon->taxon_order)
        {
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["order"] = $name->id;
        }
        if(@$string = $taxon->taxon_family)
        {
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["family"] = $name->id;
        }
        if(@$string = $taxon->taxon_genus)
        {
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["genus"] = $name->id;
        }
        if(@$taxon->taxon_family && !@$taxon->taxon_genus && @preg_match("/^([^ ]+) /", $taxon->scientific_name, $arr))
        {
            $string = $arr[1];
            $name = new Name(Name::insert($string));
            //$name->make_scientific();
            $name_ids["genus"] = $name->id;
        }
        if(@$taxon->name_id) $name_ids[] = $taxon->name_id;
        
        $parent_hierarchy_entry = null;
        foreach($name_ids as $rank => $id)
        {
            $params = array();
            $params["name_id"] = $id;
            if($parent_hierarchy_entry)
            {
                if($parent_hierarchy_entry->ancestry) $params["ancestry"] = $parent_hierarchy_entry->ancestry."|".$parent_hierarchy_entry->name_id;
                else $params["ancestry"] = $parent_hierarchy_entry->name_id;
            }
            
            $params["hierarchy_id"] = $hierarchy->id;
            if($rank) $params["rank_id"] = Rank::insert($rank);
            if($parent_hierarchy_entry) $params["parent_id"] = $parent_hierarchy_entry->id;
            
            
            if(!$rank && @$taxon->identifier) $params["identifier"] = $taxon->identifier;
            
            $mock_hierarchy_entry = Functions::mock_object("HierarchyEntry", $params);
            
            $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($mock_hierarchy_entry));
            $parent_hierarchy_entry = $hierarchy_entry;
        }
        
        if($parent_hierarchy_entry) return $parent_hierarchy_entry->id;
        return 0;
    }
}

?>