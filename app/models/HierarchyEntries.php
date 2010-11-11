<?php

class HierarchyEntry extends MysqlBase
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
        $result = $mysqli->query("SELECT * FROM hierarchy_entries");
        while($result && $row=$result->fetch_assoc())
        {
            $all[] = new HierarchyEntry($row);
        }
        return $all;
    }
    
    public function split_from_concept_static($hierarchy_entry_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $entry = new HierarchyEntry($hierarchy_entry_id);
        if(!$entry || @!$entry->id) return null;
        
        $result = $mysqli->query("SELECT he2.id, he2.taxon_concept_id FROM hierarchy_entries he JOIN hierarchy_entries he2 USING (taxon_concept_id) WHERE he.id=$hierarchy_entry_id");
        if($result && $row=$result->fetch_assoc())
        {
            $count = $result->num_rows;
            // if there is only one member in the entry's concept there is no need to split it
            if($count > 1)
            {
                $taxon_concept_id = TaxonConcept::insert();
                
                $mysqli->update("UPDATE taxon_concepts SET published=$entry->published, vetted_id=$entry->vetted_id WHERE id=$taxon_concept_id");
                $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE taxon_concept_names SET taxon_concept_id=$taxon_concept_id WHERE source_hierarchy_entry_id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN random_hierarchy_images rhi ON (he.id=rhi.hierarchy_entry_id) SET rhi.taxon_concept_id=he.taxon_concept_id WHERE he.taxon_concept_id=$hierarchy_entry_id");
                return $taxon_concept_id;
            }
        }
        return null;
    }
    
    public static function move_to_concept_static($hierarchy_entry_id, $taxon_concept_id, $force_move = false)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if(!$force_move && self::entry_move_effects_other_hierarchies($hierarchy_entry_id, $taxon_concept_id))
        {
            echo "can't make this move\n";
            return false;
        }
        
        $result = $mysqli->query("SELECT he2.id, he2.taxon_concept_id FROM hierarchy_entries he JOIN hierarchy_entries he2 USING (taxon_concept_id) WHERE he.id=$hierarchy_entry_id");
        if($result && $row=$result->fetch_assoc())
        {
            // the entry is already in the new concept so return
            if($row['taxon_concept_id'] == $taxon_concept_id) return true;
            
            $count = $result->num_rows;
            if($count == 1)
            {
                //// if there is just one member of the group, then supercede the group with the new one
                TaxonConcept::supercede_by_ids($taxon_concept_id, $row['taxon_concept_id']);
            }else
            {
                $old_taxon_concept_id = $row['taxon_concept_id'];
                // if there is more than one member, just update the one entry
                $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE taxon_concept_names SET taxon_concept_id=$taxon_concept_id WHERE source_hierarchy_entry_id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN random_hierarchy_images rhi ON (he.id=rhi.hierarchy_entry_id) SET rhi.taxon_concept_id=he.taxon_concept_id WHERE he.taxon_concept_id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) JOIN data_objects_taxon_concepts dotc ON (dohe.data_object_id=dotc.data_object_id) SET dotc.taxon_concept_id=he.taxon_concept_id WHERE he.id=$hierarchy_entry_id AND dotc.taxon_concept_id=$old_taxon_concept_id");
                
                $mysqli->update("UPDATE taxon_concepts SET published=0, vetted_id=0 WHERE id IN ($taxon_concept_id, $old_taxon_concept_id)");
                
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.published=he.published WHERE tc.id=$taxon_concept_id AND he.published!=0");
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.vetted_id=he.vetted_id WHERE tc.id=$taxon_concept_id AND he.vetted_id!=0");
                
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.published=he.published WHERE tc.id=$old_taxon_concept_id AND he.published!=0");
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.vetted_id=he.vetted_id WHERE tc.id=$old_taxon_concept_id AND he.vetted_id!=0");
            }
        }
    }
    
    private static function entry_move_effects_other_hierarchies($hierarchy_entry_id, $new_taxon_concept_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $counts = array();
        $result = $mysqli->query("SELECT he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE (he.id=$hierarchy_entry_id OR he.taxon_concept_id=$new_taxon_concept_id) AND h.complete=1");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            if(isset($counts[$hierarchy_id])) return true;
            $counts[$hierarchy_id] = 1;
        }
        
        return false;
    }
    
    
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
    
    public function references()
    {
        $references = array();
        $result = $this->mysqli->query("SELECT ref_id FROM hierarchy_entries_refs WHERE hierarchy_entry_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $references[] = new Reference($row["ref_id"]);
        }
        return $references;
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
    
    function ranked_ancestry()
    {
        $ancestry = "";
        
        if($this->parent_id)
        {
            $parent = $this->parent();
            $parent_ranked_ancestry = $parent->ranked_ancestry();
            
            if($parent_ranked_ancestry) $ancestry = $parent_ranked_ancestry."|";
            if(@$parent->rank()->id) $ancestry .= $parent->rank()->label;
            $ancestry .= ":".$parent->name()->string;
        }
        
        return $ancestry;
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
        if($result && $row=$result->fetch_assoc()) $count = $row["count"] - 1;
        
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
        
        @usort($children, "Functions::cmp_hierarchy_entries");
        
        return $children;
    }
    
    function synonyms()
    {
        $synonyms = array();
        
        $result = $this->mysqli->query("SELECT * FROM synonyms WHERE hierarchy_entry_id=".$this->id);
        while($result && $row=$result->fetch_assoc()) $synonyms[] = new Synonym($row);
        $result->free();
        
        @usort($synonyms, "Functions::cmp_hierarchy_entries");
        
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
        $this->mysqli->insert("INSERT IGNORE INTO agents_hierarchy_entries VALUES ($this->id, $agent_id, $agent_role_id, $view_order)");
    }
    
    public function add_synonym($name_id, $relation_id, $language_id, $preferred, $vetted_id = 0, $published = 0)
    {
        if(!$name_id) return 0;
        if(!$relation_id) $relation_id = 0;
        if(!$language_id) $language_id = 0;
        if(!$preferred) $preferred = 0;
        Synonym::insert(array(  'name_id'               => $name_id,
                                'synonym_relation_id'   => $relation_id,
                                'language_id'           => $language_id,
                                'hierarchy_entry_id'    => $this->id,
                                'preferred'             => $preferred,
                                'hierarchy_id'          => $this->hierarchy_id,
                                'vetted_id'             => $vetted_id,
                                'published'             => $published));
    }
    
    public function add_data_object($data_object_id)
    {
        if(!$data_object_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_hierarchy_entries VALUES ($this->id, $data_object_id)");
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_taxon_concepts VALUES ($this->taxon_concept_id, $data_object_id)");
    }
    
    public function unpublish_refs()
    {
        $this->mysqli->update("UPDATE hierarchy_entries he JOIN hierarchy_entries_refs her ON (he.id=her.hierarchy_entry_id) JOIN refs r ON (her.ref_id=r.id) SET r.published=0 WHERE he.id=$this->id");
    }
    
    public function add_reference($reference_id)
    {
        if(!$reference_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs VALUES ($this->id, $reference_id)");
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
    
    static function create_entries_for_taxon($taxon, $hierarchy_id)
    {
        $name_ids = array();
        if(@$string = $taxon['kingdom'])
        {
            $name = new Name(Name::insert($string));
            $name_ids["kingdom"] = $name->id;
        }
        if(@$string = $taxon['phylum'])
        {
            $name = new Name(Name::insert($string));
            $name_ids["phylum"] = $name->id;
        }
        if(@$string = $taxon['class'])
        {
            $name = new Name(Name::insert($string));
            $name_ids["class"] = $name->id;
        }
        if(@$string = $taxon['order'])
        {
            $name = new Name(Name::insert($string));
            $name_ids["order"] = $name->id;
        }
        if(@$string = $taxon['family'])
        {
            $name = new Name(Name::insert($string));
            $name_ids["family"] = $name->id;
        }
        if(@$string = $taxon['genus'])
        {
            $name = new Name(Name::insert($string));
            $name_ids["genus"] = $name->id;
        }
        if(@$taxon['family'] && !@$taxon['genus'] && @preg_match("/^([^ ]+) /", $taxon['scientific_name'], $arr))
        {
            $string = $arr[1];
            $name = new Name(Name::insert($string));
            $name_ids["genus"] = $name->id;
        }
        
        // the base level scientific_name. Unsure of the rank at this point
        if(@$taxon['name_id']) $name_ids[] = $taxon['name_id'];
        
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
            
            $params["hierarchy_id"] = $hierarchy_id;
            if($rank) $params["rank_id"] = Rank::insert($rank);
            if($parent_hierarchy_entry) $params["parent_id"] = $parent_hierarchy_entry->id;
            
            // if there is no rank then we have the scientific_name
            if(!$rank)
            {
                $params["identifier"] = $taxon['identifier'];
                $params["source_url"] = $taxon['source_url'];
            }
            
            $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($params));
            $parent_hierarchy_entry = $hierarchy_entry;
        }
        
        // returns the entry object for the scientific_name
        if($parent_hierarchy_entry) return $parent_hierarchy_entry;
        return 0;
    }
    
    static function insert($parameters, $force = false)
    {
        if(!$parameters) return 0;
        if(!is_array($parameters)) return 0;
        
        if(!$force && $result = self::find($parameters)) return $result;
        
        if(@!$parameters['taxon_concept_id']) $parameters['taxon_concept_id'] = TaxonConcept::insert();
        if(@!$parameters['guid']) $parameters['guid'] = Functions::generate_guid();
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($parameters)
    {
        if(@!$parameters['name_id']) $parameters['name_id'] = 0;
        if(@!$parameters['parent_id']) $parameters['parent_id'] = 0;
        if(@!$parameters['identifier']) $parameters['identifier'] = '';
        
        // look for entries with the SAME NAME and the SAME PARENT, in the SAME HIERARCHY
        $result = $GLOBALS['db_connection']->query("SELECT SQL_NO_CACHE id, identifier, guid
            FROM hierarchy_entries
            WHERE name_id=". $parameters['name_id'] ."
            AND parent_id=". $parameters['parent_id'] ."
            AND hierarchy_id=". $parameters['hierarchy_id']);
        
        // check the results for duplicates
        while($result && $row=$result->fetch_assoc())
        {
            // each has an identifier, but they are not the same
            if($row['identifier'] && $parameters['identifier'] && $parameters['identifier'] != $row['identifier']) continue;
            
            // existing entry has no identifier - so reuse it and update its identifier
            if(!$row['identifier'] && $parameters['identifier'])
            {
                $GLOBALS['db_connection']->update("UPDATE hierarchy_entries SET identifier='". $GLOBALS['db_connection']->escape($parameters['identifier']) ."' WHERE id=".$row['id']);
            }
            return $row['id'];
        }
        return false;
    }
}

?>