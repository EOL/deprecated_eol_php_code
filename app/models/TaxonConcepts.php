<?php
namespace php_active_record;

class TaxonConcept extends ActiveRecord
{
    public static $has_many = array(
            array('hierarchy_entries'),
            array('hierarchies', 'through' => 'hierarchy_entries'),
            array('names', 'through' => 'hierarchy_entries'),
            array('ranks', 'through' => 'hierarchy_entries')
        );
    
    function supercede($taxon_concept_id)
    {
        self::supercede_by_ids($taxon_concept_id, $this->id);
    }

    public static function unlock_classifications_by_id($id)
    {

      $mysqli =& $GLOBALS['mysqli_connection'];
      $mysqli->update("DELETE FROM taxon_classifications_locks WHERE taxon_concept_id=$id");

    }
    
    public static function supercede_by_ids($id1, $id2, $update_collection_items = false)
    {
        if($id1 == $id2) return true;
        if(!$id1 || !$id2) return false;
        // $id1 = TaxonConcept::get_superceded_by($id1);
        // $id2 = TaxonConcept::get_superceded_by($id2);
        // if($id1 == $id2) return true;
        
        // always replace the larger ID with the smaller one
        if($id2 < $id1) list($id1, $id2) = array($id2, $id1);
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        // at this point ID2 is the one going away
        // ID2 is being superceded by ID1
        debug("Matching hierarchy_entries to its superceded taxon_concept_id($id1)");
        $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.taxon_concept_id=$id1, tc.supercedure_id=$id1 WHERE taxon_concept_id=$id2");
        
        // updating TCN => all names linked to ID2 are getting linked to ID1
        debug("Matching users_data_objects,taxon_concept_names, data_objects_taxon_concepts, random_hierarchy_images, taxon_concepts_flattened to its superceded taxon_concept_id($id1)");
        
        $mysqli->update("UPDATE IGNORE users_data_objects SET taxon_concept_id=$id1 WHERE taxon_concept_id=$id2");
        $mysqli->update("UPDATE IGNORE taxon_concept_names SET taxon_concept_id=$id1 WHERE taxon_concept_id=$id2");
        $mysqli->update("DELETE FROM taxon_concept_names WHERE taxon_concept_id=$id2");
        $mysqli->update("UPDATE IGNORE data_objects_taxon_concepts SET taxon_concept_id=$id1 WHERE taxon_concept_id=$id2");
        $mysqli->update("DELETE FROM data_objects_taxon_concepts WHERE taxon_concept_id=$id2");
        $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN random_hierarchy_images rhi ON (he.id=rhi.hierarchy_entry_id) SET rhi.taxon_concept_id=he.taxon_concept_id WHERE he.taxon_concept_id=$id2");
        $mysqli->update("UPDATE IGNORE taxon_concepts_flattened SET taxon_concept_id=$id1 WHERE taxon_concept_id=$id2");
        $mysqli->update("DELETE FROM taxon_concepts_flattened WHERE taxon_concept_id=$id2");
        $mysqli->update("UPDATE IGNORE taxon_concepts_flattened SET ancestor_id=$id1 WHERE ancestor_id=$id2");
        $mysqli->update("DELETE FROM taxon_concepts_flattened WHERE ancestor_id=$id2");
        
        if($update_collection_items)
        {
            $updating_collection_items = false;
            $result = $mysqli->query("SELECT 1 FROM collection_items WHERE collected_item_id=$id2 AND collected_item_type='TaxonConcept' LIMIT 1");
            if($result && $row=$result->fetch_assoc())
            {
                $updating_collection_items = true;
                $mysqli->update("UPDATE IGNORE collection_items SET collected_item_id=$id1 WHERE collected_item_id=$id2 AND collected_item_type='TaxonConcept'");
                self::reindex_collection_items($id1);
            }
            
            if($updating_collection_items)
            {
                $solr = new SolrAPI(SOLR_SERVER, 'collection_items');
                $solr->delete("object_type:TaxonConcept AND object_id:$id2");
                $mysqli->update("DELETE FROM collection_items WHERE collected_item_id=$id2 AND collected_item_type='TaxonConcept'");
            }
        }
        $mysqli->end_transaction();
    }
    
    public static function reindex_descendants_objects($taxon_concept_id)
    {
        $data_object_ids = Tasks::get_descendant_objects($taxon_concept_id);
        if($data_object_ids)
        {
            $object_indexer = new DataObjectAncestriesIndexer();
            $object_indexer->index_data_objects($data_object_ids);

            $search_indexer = new SiteSearchIndexer();
            $search_indexer->index_type('DataObject', $data_object_ids);
        }
    }
    
    public static function count_descendants_objects($taxon_concept_id)
    {
        $solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        $main_query = "ancestor_id:$taxon_concept_id&fl=data_object_id";
        return $solr->count_results($main_query);
    }
    
    public static function reindex_for_search($taxon_concept_id)
    {
        if(!$taxon_concept_id) return false;
        $ids = array($taxon_concept_id);
        $search_indexer = new SiteSearchIndexer();
        $search_indexer->index_type('TaxonConcept', $ids);
    }
    
    public static function reindex_descendants($taxon_concept_id)
    {
        $taxon_concept_ids = array();
        $query = "SELECT taxon_concept_id FROM taxon_concepts_flattened WHERE ancestor_id = $taxon_concept_id";
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row)
        {
            $taxon_concept_ids[] = $row[0];
        }
        print_r($taxon_concept_ids);
        $taxon_concept_ids[] = $taxon_concept_id;
        $search_indexer = new SiteSearchIndexer();
        $search_indexer->index_type('TaxonConcept', $taxon_concept_ids);
    }
    
    public static function count_descendants($taxon_concept_id)
    {
        $result = $GLOBALS['db_connection']->query("SELECT count(*) as count FROM taxon_concepts_flattened WHERE ancestor_id = $taxon_concept_id");
        if($result && $row = $result->fetch_assoc())
        {
            return $row['count'];
        }
        return 0;
    }
    
    public static function reindex_collection_items($taxon_concept_id)
    {
        $collection_item_ids = array();
        $query = "SELECT id FROM collection_items WHERE collected_item_id = $taxon_concept_id AND collected_item_type = 'TaxonConcept'";
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row)
        {
            $collection_item_ids[] = $row[0];
        }
        if($collection_item_ids)
        {
            $indexer = new CollectionItemIndexer();
            $indexer->index_collection_items($collection_item_ids);
        }
    }
    
    public static function get_superceded_by($taxon_concept_id)
    {
        $result = $GLOBALS['db_connection']->query("SELECT supercedure_id FROM taxon_concepts WHERE id=$taxon_concept_id AND supercedure_id!=0");
        if($result && $row=$result->fetch_assoc())
        {
            return self::get_superceded_by($row['supercedure_id']);
        }
        return $taxon_concept_id;
    }
    
    public static function media_counts($taxon_concept_id)
    {
        $solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        $response = $solr->raw_query("published:1 AND ancestor_id:$taxon_concept_id AND visible_ancestor_id:$taxon_concept_id AND (trusted_ancestor_id:$taxon_concept_id OR unreviewed_ancestor_id:$taxon_concept_id) NOT data_subtype_id:".DataType::map()->id." NOT is_translation:true&facet.field=data_type_id&facet=on&rows=0");
        $facet_response = $response->facet_counts->facet_fields->data_type_id;
        $facets = array();
        foreach($facet_response as $index => $facet_value)
        {
            if($index % 2 == 1) continue;
            $data_type_id = $facet_value;
            if(in_array($data_type_id, DataType::image_type_ids())) $key = 'image';
            elseif(in_array($data_type_id, DataType::text_type_ids())) $key = 'text';
            elseif(in_array($data_type_id, DataType::video_type_ids())) $key = 'video';
            elseif(in_array($data_type_id, DataType::sound_type_ids())) $key = 'sound';
            else $key = $data_type_id;
            if(!isset($facets[$key])) $facets[$key] = 0;
            $facets[$key] += $facet_response[$index + 1];
        }
        return $facets;
    }
    
    function rank()
    {
        $string = "";
        
        $result = $this->mysqli->query("SELECT MAX(rank_id) as rank_id FROM hierarchy_entries WHERE taxon_concept_id=$this->id AND rank_id!=0");
        if($result && $row=$result->fetch_assoc())
        {
            $rank = Rank::find($row["rank_id"]);
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
        
        return new Name(array("string" => $string));
    }
    
    function names()
    {
        $names = array();
        
        $ids = $this->name_ids();
        foreach($ids as $id)
        {
            $names[] = Name::find($id);
        }
        
        return $names;
    }
    
    function parents()
    {
        $parents = array();
        $result = $this->mysqli->query("SELECT DISTINCT he2.taxon_concept_id FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.parent_id=he2.id) WHERE he1.taxon_concept_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            $parents[] = TaxonConcept::find($row["taxon_concept_id"]);
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
            $children[] = TaxonConcept::find($row["taxon_concept_id"]);
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
            $synonyms[] = Synonym::find($row["id"]);
        }
        if($result && $result->num_rows) $result->free();
        usort($synonyms, "Functions::cmp_hierarchy_entries");
        return $synonyms;
    }
    
    function homonyms()
    {
        if(!array_diff($this->name_ids(), Name::unassigned_ids())) return array();
        $ids = array();
        
        $result = $this->mysqli->query("SELECT taxon_concept_id FROM hierarchy_entries he WHERE he.taxon_concept_id!=0 AND he.name_id IN (".implode(", ", $this->name_ids()).") AND he.id NOT IN (".implode(", ", $this->hierarchy_entry_ids()).")");
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
            $hierarchies[] = Hierarchy::find($row["hierarchy_id"]);
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
    
    static function get_name($id)
    {
        foreach($GLOBALS['mysqli_connection']->iterate("SELECT n.string FROM hierarchy_entries he JOIN names n ON (he.name_id=n.id) WHERE he.taxon_concept_id=$id AND he.published=1 AND he.visibility_id=". Visibility::visible()->id." LIMIT 1") as $row_num => $row)
        {
            return $row['string'];
        }
        return null;
    }
}

?>
