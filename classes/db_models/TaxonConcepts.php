<?php

class TaxonConcept extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    function supercede($taxon_concept_id)
    {
        self::supercede_by_ids($taxon_concept_id, $this->id);
    }
    
    public static function supercede_by_ids($id1, $id2)
    {
        if($id1 == $id2) return true;
        if($id2 < $id1) list($id1, $id2) = array($id2, $id1);
        
        if(!$id1 || !$id2) return false;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.taxon_concept_id=$id1, tc.supercedure_id=$id1 WHERE taxon_concept_id=$id2");
        
        Tasks::update_taxon_concept_names($id1);
    }

    function rank()
    {
        $string = "";
        
        $result = $this->mysqli->query("SELECT MAX(rank_id) as rank_id FROM hierarchy_entries WHERE taxon_concept_id=$this->id AND rank_id!=0");
        if($result && $row=$result->fetch_assoc())
        {
            $rank = new Rank($row["rank_id"]);
            $string = @$rank->label;
        }
        if($result && $result->num_rows) $result->free();
        
        return $string;
    }
    
    function name()
    {
        $string = "";
        
        $names = $this->names();
        foreach($names as $name)
        {
            if(strlen($name->string) > strlen($string)) $string = $name->string;
        }
        if(count($names)>1) $string = "$string <small><i>modified name</i></small>";
        
        return Functions::mock_object("Name", array("string" => $string));
    }
    
    function names()
    {
        $names = array();
        
        $ids = $this->name_ids();
        foreach($ids as $id)
        {
            $names[] = new Name($id);
        }
        
        return $names;
    }
    
    function parents()
    {
        $parents = array();
        
        $result = $this->mysqli->query("SELECT DISTINCT he2.taxon_concept_id FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.parent_id=he2.id) WHERE he1.taxon_concept_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            $parents[] = new TaxonConcept($row["taxon_concept_id"]);
        }
        if($result && $result->num_rows) $result->free();
        
        return $parents;
    }
    
    function children()
    {
        $children = array();
        
        $result = $this->mysqli->query("SELECT DISTINCT he2.taxon_concept_id FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.id=he2.parent_id) WHERE he1.taxon_concept_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            $children[] = new TaxonConcept($row["taxon_concept_id"]);
        }
        if($result && $result->num_rows) $result->free();
        
        //usort($children, "Functions::cmp_hierarchy_entries");
        
        return $children;
    }
    
    function siblings()
    {
        $siblings = array();
        
        $parents = $this->parents();
        foreach($parents as $parent)
        {
            $children = $parent->children();
            foreach($children as $child)
            {
                if($child->id != $this->id) $siblings[$child->id] = $child;
            }
        }
        
        return $siblings;
    }
    
    function synonyms()
    {
        $synonyms = array();
        
        $result = $this->mysqli->query("SELECT s.id FROM hierarchy_entries he JOIN synonyms s ON (s.hierarchy_entry_id=he.id) WHERE he.taxon_concept_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            $synonyms[] = new Synonym($row["id"]);
        }
        if($result && $result->num_rows) $result->free();
        
        usort($synonyms, "Functions::cmp_hierarchy_entries");
        
        return $synonyms;
    }
    
    function homonyms()
    {
        if(!array_diff($this->name_ids(), Name::unassigned_ids())) return array();
        $ids = array();
        
        $result = $this->mysqli->query("SELECT taxon_concept_id FROM hierarchy_entries he WHERE he.taxon_concept_id!=0 AND he.name_id IN (".implode(", ",$this->name_ids()).") AND he.id NOT IN (".implode(", ", $this->hierarchy_entry_ids()).")");
        while($result && $row=$result->fetch_assoc())
        {
            $ids[$row["taxon_concept_id"]] = 1;
        }
        if($result && $result->num_rows) $result->free();
        
        return array_keys($ids);
    }
    
    function hierarchies()
    {
        $hierarchies = array();
        
        $result = $this->mysqli->query("SELECT hierarchy_id FROM hierarchy_entries WHERE taxon_concept_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchies[] = new Hierarchy($row["hierarchy_id"]);
        }
        if($result && $result->num_rows) $result->free();
        
        return $hierarchies;
    }
    
    function hierarchy_entry_ids()
    {
        $ids = array();
        
        $result = $this->mysqli->query("SELECT id FROM hierarchy_entries WHERE taxon_concept_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $ids[] = $row["id"];
        }
        if($result && $result->num_rows) $result->free();
        
        return $ids;
    }
    
    function hierarchy_entries()
    {
        $hierarchy_entries = array();
        
        $result = $this->mysqli->query("SELECT id FROM hierarchy_entries WHERE taxon_concept_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_entries[] = new HierarchyEntry($row["id"]);
        }
        if($result && $result->num_rows) $result->free();
        
        return $hierarchy_entries;
    }
    
    function name_ids()
    {
        $ids = array();
                
        $result = $this->mysqli->query("SELECT name_id FROM hierarchy_entries WHERE taxon_concept_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $ids[$row["name_id"]] = 2;
        }
        if($result && $result->num_rows) $result->free();
        
        $ids = array_keys($ids);
        
        return $ids;
    }
    
    function mock_all_names()
    {
        $names = array();
        $names["names"] = array();
        $names["synonyms"] = array();
        $names["children"] = array();
                
        $result = $this->mysqli->query("(SELECT n.id, n.string, n.canonical_form_id, 'names' as type FROM hierarchy_entries he JOIN names n ON (he.name_id=n.id) WHERE he.taxon_concept_id=$this->id) UNION (SELECT n.id, n.string, n.canonical_form_id, 'synonyms' as type FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) JOIN names n ON (s.name_id=n.id) WHERE he.taxon_concept_id=$this->id AND s.language_id=0)");
        while($result && $row=$result->fetch_assoc())
        {
            $names[$row["type"]][$row["id"]] = Functions::mock_object("Name", array("id" => $row["id"], "string" => $row["string"], "canonical_form_id" => $row["canonical_form_id"]));
        }
        if($result->num_rows) $result->free();
        
        
        $result = $this->mysqli->query("(SELECT distinct he2.taxon_concept_id, n.id, n.string, n.canonical_form_id, 'names' as type FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.id=he2.parent_id) JOIN names n ON (he2.name_id=n.id) WHERE he1.taxon_concept_id=$this->id) UNION (SELECT distinct he2.taxon_concept_id, n.id, n.string, n.canonical_form_id, 'synonyms' as type  FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.id=he2.parent_id) JOIN synonyms s ON (he2.id=s.hierarchy_entry_id) JOIN names n ON (s.name_id=n.id) WHERE he1.taxon_concept_id=$this->id AND s.language_id=0)");
        while($result && $row=$result->fetch_assoc())
        {
            $names["children"][$row["taxon_concept_id"]][$row["type"]][$row["id"]] = Functions::mock_object("Name", array("id" => $row["id"], "string" => $row["string"], "canonical_form_id" => $row["canonical_form_id"]));
        }
        if($result && $result->num_rows) $result->free();
                
        return $names;
    }
    
    function mock_hierarchy_entries()
    {
        $hierarchy_entries = array();
                
        $result = $this->mysqli->query("SELECT he.id, he.hierarchy_id, he.ancestry, n.id name_id, n.canonical_form_id FROM hierarchy_entries he JOIN names n ON (he.name_id=n.id) WHERE he.taxon_concept_id=$this->id");
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_entries[$row["id"]] = Functions::mock_object("HierarchyEntry", array("id" => $row["id"], "hierarchy_id" => $row["hierarchy_id"], "ancestry" => $row["ancestry"], "name_id" => $row["name_id"], "canonical_form_id" => $row["canonical_form_id"]));
        }
        if($result->num_rows) $result->free();
                
        return $hierarchy_entries;
    }
    
    static function insert($split_from = null)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if($split_from) return $mysqli->insert("INSERT INTO taxon_concepts (id, split_from) VALUES (NULL, $split_from)");
        else return $mysqli->insert("INSERT INTO taxon_concepts (id) VALUES (NULL)");
    }
}

?>