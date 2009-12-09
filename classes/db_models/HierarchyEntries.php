<?php

class HierarchyEntry extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    // public static function split_entry_from_concept_static($hierarchy_entry_id)
    //     {
    //         $mysqli =& $GLOBALS['mysqli_connection'];
    //         
    //         $result = $mysqli->query("SELECT he2.id, he2.taxon_concept_id FROM hierarchy_entries he JOIN hierarchy_entries he2 USING (taxon_concept_id) WHERE he.id=$hierarchy_entry_id AND he2.id!=he1.id");
    //         if($result && $row=$result->fetch_assoc())
    //         {
    //             $taxon_concept_id = $row['taxon_concept_id'];
    //             
    //             // create a new concept that this entry will go into
    //             $new_concept = TaxonConcept::insert();
    //             
    //             // set the concept of this entry to the new one
    //             $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$new_concept->id WHERE id=$hierarchy_entry_id");
    //             
    //             // set the split_from attribute of the new concept
    //             $mysqli->update("UPDATE taxon_concepts SET split_from=$taxon_concept_id WHERE id=$hierarchy_entry_id");
    //             
    //             // // rebuild the taxon_concept_names for the two entries
    //             //Tasks::update_taxon_concept_names($this->taxon_concept_id);
    //             //Tasks::update_taxon_concept_names($new_concept->id);
    //             
    //             return $new_concept->id;
    //         }
    //         return null;
    //     }
    //     
    //     public function move_to_concept_static($hierarchy_entry_id, $taxon_concept_id)
    //     {
    //         $mysqli =& $GLOBALS['mysqli_connection'];
    //         
    //         $result = $mysqli->query("SELECT he2.id, he2.taxon_concept_id FROM hierarchy_entries he JOIN hierarchy_entries he2 USING (taxon_concept_id) WHERE he.id=$hierarchy_entry_id");
    //         if($result && $row=$result->fetch_assoc())
    //         {
    //             // the entry is already in the new concept so return
    //             if($row['taxon_concept_id'] == $taxon_concept_id) return true;
    //             
    //             $count = $result->num_rows;
    //             if($count == 1)
    //             {
    //                 // if there is just one member of the group, then supercede the group with the new one
    //                 TaxonConcept::supercede_by_ids($taxon_concept_id, $row['taxon_concept_id']);
    //             }else
    //             {
    //                 // if there is more than one member, just update the one entry
    //                 $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$this->id");
    //                 //Tasks::update_taxon_concept_names($this->taxon_concept_id);
    //                 //Tasks::update_taxon_concept_names($taxon_concept_id);
    //             }
    //         }
    //     }
    //     
    //     public function move_to_concept($taxon_concept_id)
    //     {
    //         $mysqli =& $GLOBALS['mysqli_connection'];
    //         
    //         $result = $mysqli->query("SELECT count(*) as count FROM hierarchy_entries WHERE taxon_concept_id=$this->taxon_concept_id");
    //         if($result && $row=$result->fetch_assoc())
    //         {
    //             $count = $row['count'];
    //             if($count == 1)
    //             {
    //                 // if there is just one member of the group, then supercede the group with the new one
    //                 TaxonConcept::supercede_by_ids($taxon_concept_id, $this->taxon_concept_id);
    //             }else
    //             {
    //                 // if there are more than one members, just update the one entry
    //                 $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$this->id");
    //                 //Tasks::update_taxon_concept_names($this->taxon_concept_id);
    //                 //Tasks::update_taxon_concept_names($taxon_concept_id);
    //             }
    //         }
    //     }
    
    function name()
    {
        if(@$this->name) return $this->name;
        
        $this->name = new Name($this->name_id);
        return $this->name;
    }
    
    function taxon_concept()
    {
        if(@$this->taxon_concept) return $this->taxon_concept;
        
        $this->taxon_concept = new TaxonConcept($this->taxon_concept_id);
        return $this->taxon_concept;
    }
    
    function hierarchy()
    {
        if(@$this->hierarchy) return $this->hierarchy;
        
        $this->hierarchy = new Hierarchy($this->hierarchy_id);
        return $this->hierarchy;
    }
    
    function rank()
    {
        if(@$this->rank) return $this->rank;
        
        $this->rank = new Rank($this->rank_id);
        return $this->rank;
    }
    
    function set_taxon_concept_id($taxon_concept_id)
    {
        if($this->taxon_concept_id)
        {
            if($this->taxon_concept_id!=$taxon_concept_id) $this->taxon_concept()->supercede($taxon_concept_id);
        }else
        {
            $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$this->id");
        }
    }
    
    function ancestry_names()
    {
        $ancestry = "";
        
        if($this->parent_id)
        {
            $parent = $this->parent();
            $parent_ancestry_names = $parent->ancestry_names();
            
            if($parent_ancestry_names) $ancestry = $parent_ancestry_names."|";
            $ancestry .= $parent->name()->string;
        }
        
        return $ancestry;
    }
    
    function agents()
    {
        $agents = array();
        
        $result = $this->mysqli->query("SELECT * FROM agents_hierarchy_entries WHERE hierarchy_entry_id=".$this->id." ORDER BY view_order ASC");
        while($result && $row=$result->fetch_assoc())
        {
            $agents[] = new AgentHierarchyEntry($row);
        }
        $result->free();
        
        return $agents;
    }
    
    function parent()
    {
        if($this->parent_id) return new HierarchyEntry($this->parent_id);
        
        return false;
    }
    
    function taxon_concept_parents()
    {
        $taxon_concept_ids = array();
        $parents = array();
        
        $result = $this->mysqli->query("SELECT he2.taxon_concept_id, he2.id FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.parent_id=h2.id) WHERE h1.taxon_concept_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            if($row["taxon_concept_id"]) $taxon_concept_ids[$row["taxon_concept_id"]] = 1;
            else $parents[] = new HierarchyEntry($row["id"]);
        }
        $result->free();
        
        foreach($taxon_concept_ids as $k => $v)
        {
            $parents[] = new TaxonConcept($k);
        }
        
        return $parents;
    }
    
    function number_of_children()
    {
        $count = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(id) as count FROM hierarchy_entries WHERE lft BETWEEN $this->lft AND $this->rgt AND hierarchy_id=$this->hierarchy_id");
        if($result && $row=$result->fetch_assoc()) $count = $row["count"];
        
        return $count;
    }
    
    function number_of_children_synonyms()
    {
        $count = 0;
        
        $result = $this->mysqli->query("SELECT COUNT(s.id) as count FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.lft BETWEEN $this->lft AND $this->rgt AND he.hierarchy_id=$this->hierarchy_id");
        if($result && $row=$result->fetch_assoc()) $count = $row["count"];
        
        return $count;
    }
    
    function children()
    {
        $children = array();
        
        $result = $this->mysqli->query("SELECT * FROM hierarchy_entries WHERE parent_id=".$this->id);
        while($result && $row=$result->fetch_assoc()) $children[] = new HierarchyEntry($row);
        $result->free();
        
        usort($children, "Functions::cmp_hierarchy_entries");
        
        return $children;
    }
    
    function synonyms()
    {
        $synonyms = array();
        
        $result = $this->mysqli->query("SELECT * FROM synonyms WHERE hierarchy_entry_id=".$this->id);
        while($result && $row=$result->fetch_assoc()) $synonyms[] = new Synonym($row);
        $result->free();
        
        usort($synonyms, "Functions::cmp_hierarchy_entries");
        
        return $synonyms;
    }
    
    function top_images()
    {
        $top_images = array();
        
        $result = $this->mysqli->query("SELECT data_object_id FROM top_images WHERE hierarchy_entry_id=".$this->id." ORDER BY view_order ASC");
        while($result && $row=$result->fetch_assoc())
        {
            $top_images[] = new DataObject($this->mysqli, $row["data_object_id"]);
        }
        $result->free();
        
        return $top_images;
    }
    
    function is_in_ancestry_of($hierarchy_entry)
    {
        $canonical_form_id = $this->name()->canonical_form_id;
        
        $ancestry = $hierarchy_entry->ancestry;
        $nodes = explode("|", $ancestry);
        foreach($nodes as $id)
        {
            //echo "IIAO: $id - $this->name_id<br>";
            if($id == $this->name_id) return true;
            $name = new Name($id);
            if($name->canonical_form_id == $canonical_form_id) return true;
        }
        
        return false;
    }
    
    public function delete_agents()
    {
        $this->mysqli->insert("DELETE FROM agents_hierarchy_entries WHERE hierarchy_entry_id=$this->id");
    }
    public function delete_common_names()
    {
        $this->mysqli->insert("DELETE FROM synonyms WHERE hierarchy_entry_id=$this->id AND language_id!=0 AND language_id!=". Language::insert('scientific name'));
    }
    public function delete_synonyms()
    {
        $this->mysqli->insert("DELETE FROM synonyms WHERE hierarchy_entry_id=$this->id AND (language_id=0 OR language_id=". Language::insert('scientific name').")");
    }
    
       
    public function add_agent($agent_id, $agent_role_id, $view_order)
    {
        if(!$agent_id) return false;
        $this->mysqli->insert("INSERT INTO agents_hierarchy_entries VALUES ($this->id, $agent_id, $agent_role_id, $view_order)");
    }
    
    public function add_synonym($name_id, $relation_id, $language_id, $preferred, $vetted_id = 0, $published = 0)
    {
        if(!$name_id) return 0;
        if(!$relation_id) $relation_id = 0;
        if(!$language_id) $language_id = 0;
        if(!$preferred) $preferred = 0;
        $this->mysqli->insert("INSERT INTO synonyms VALUES (NULL, $name_id, $relation_id, $language_id, $this->id, $preferred, $this->hierarchy_id, $vetted_id, $published)");
    }
    
    public static function add_child_to($node_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        $node = new HierarchyEntry($node_id);
        if(!$node->id) return false;

        $params = array();
        $params["hierarchy_id"] = $node->hierarchy_id;
        $params["parent_id"] = $node->id;
        $params["parent_id"] = $parent_hierarchy_entry->id;

        $mock_hierarchy_entry = Functions::mock_object("HierarchyEntry", $params);



        return $id;
    }
    
    public static function move_to_child_of($node_id, $child_of_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $node = new HierarchyEntry($node_id);
        $parent = new HierarchyEntry($child_of_id);
        
        // Make sure we have two valid nodes in the same hierarchy
        if(!$node->id) return false;
        if(!$parent->id) return false;
        if($parent->hierarchy_id != $node->hierarchy_id) return false;
        
        // No need to move, this branch is already a child of this parent
        if($parent->id == $node->parent_id) return false;
        
        $position = "left_of";
        if($parent->lft < $node->lft)
        {
            if($parent->rgt > $node->rgt) $position = "child_of";
            else $position = "right_of";
        }elseif($parent->rgt < $node->rgt)
        {
            // Parent is contained in this branch - cant move this to a child of itself
            return false;
        }
        
        $branch_span = $node->rgt - $node->lft + 1;
        
        if($position == "right_of") $branch_offset = $parent->rgt - $node->lft;
        else $branch_offset = $parent->rgt - $node->rgt - 1;
        
        $mysqli->begin_transaction();
        
        // Resest the branch parent
        $mysqli->query("UPDATE hierarchy_entries SET parent_id=$parent->id WHERE id=$node->id");
        $mysqli->commit();
        
        // Set the left and right values of the branch to their inverse so they can be queried for later
        $mysqli->query("UPDATE hierarchy_entries SET lft=-1*lft, rgt=-1*rgt WHERE lft between $node->lft AND $node->rgt AND hierarchy_id=$node->hierarchy_id");
        
        if($position == "right_of")
        {
            // Update the left and right values of the effected nodes
            $mysqli->query("UPDATE hierarchy_entries SET lft=lft+$branch_span WHERE lft>=$parent->rgt AND lft<$node->lft AND hierarchy_id=$node->hierarchy_id");
            $mysqli->query("UPDATE hierarchy_entries SET rgt=rgt+$branch_span WHERE rgt>=$parent->rgt AND rgt<$node->lft AND hierarchy_id=$node->hierarchy_id");
        }else
        {
            // Update the left and right values of the effected nodes
            $mysqli->query("UPDATE hierarchy_entries SET lft=lft-$branch_span WHERE lft>$node->rgt AND lft<$parent->rgt AND hierarchy_id=$node->hierarchy_id");
            $mysqli->query("UPDATE hierarchy_entries SET rgt=rgt-$branch_span WHERE rgt>$node->rgt AND rgt<$parent->rgt AND hierarchy_id=$node->hierarchy_id");
        }
        
        // Update the left and right values of the branch
        $mysqli->query("UPDATE hierarchy_entries SET depth=depth+(". ($parent->depth + 1 - $node->depth) ."), lft=(-1*lft)+($branch_offset), rgt=(-1*rgt)+($branch_offset) WHERE lft between ". (-1*$node->rgt) ." AND ". (-1*$node->lft) ." AND hierarchy_id=$node->hierarchy_id");
        
        $mysqli->end_transaction();
    }
    
    static function insert($parameters)
    {
        if(!$parameters) return 0;
        
        if(get_class($parameters)=="HierarchyEntry")
        {
            if($result = self::find_by_mock_object($parameters)) return $result;
            
            if(@!$parameters->taxon_concept_id) $parameters->taxon_concept_id = TaxonConcept::insert();
            return parent::insert_object_into($parameters, Functions::class_name(__FILE__));
        }
        
        if($result = self::find($parameters)) return $result;
        
        if(@!$parameters['taxon_concept_id']) $parameters['taxon_concept_id'] = TaxonConcept::insert();
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($parameters)
    {
        return 0;
    }
    
    static function find_by_mock_object($mock)
    {
        $search_object = clone $mock;
        
        unset($search_object->identifier);
        unset($search_object->rank_id);
        
        return parent::find_by_mock_obj($search_object, Functions::class_name(__FILE__));
    }
}

?>