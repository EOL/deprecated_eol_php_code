<?php

class HierarchyEntryIndexer
{
    private $mysqli;
    private $solr;
    private $rank_labels;
    private $objects;
    private $node_metadata;
    private $ancestries;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        
        $this->rank_labels = array();
        if($r = Rank::find('kingdom')) $this->rank_labels[$r] = 'kingdom';
        if($r = Rank::find('phylum')) $this->rank_labels[$r] = 'phylum';
        if($r = Rank::find('class')) $this->rank_labels[$r] = 'class';
        if($r = Rank::find('order')) $this->rank_labels[$r] = 'order';
        if($r = Rank::find('family')) $this->rank_labels[$r] = 'family';
        if($r = Rank::find('genus')) $this->rank_labels[$r] = 'genus';
        if($r = Rank::find('species')) $this->rank_labels[$r] = 'species';
    }
    
    public function index($hierarchy_id = NULL, $optimize = true)
    {
        $filter = "1=1";
        
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) return false;
        
        if($hierarchy_id)
        {
            $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
            $this->solr->delete("hierarchy_id:$hierarchy_id");
            $filter = "he.hierarchy_id IN ($hierarchy_id)";
        }else
        {
            $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries_swap');
            $this->solr->delete_all_documents();
        }
        
        $start = 0;
        $max_id = 0;
        $limit = 30000;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM hierarchy_entries he WHERE $filter");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<=$max_id ; $i+=$limit)
        {
            unset($this->objects);
            unset($this->ancestries);
            unset($this->node_metadata);
            
            $this->lookup_names($i, $limit, $filter);
            $this->lookup_ancestries();
            $this->lookup_synonyms($i, $limit, $filter);
            
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        }
        
        
        if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        $this->solr->commit();
        // if($optimize) $this->solr->optimize();
        
        if(!$hierarchy_id)
        {
            $this->solr->swap('hierarchy_entries_swap', 'hierarchy_entries');
        }
    }
    
    
    private function lookup_names($start, $limit, $filter = "1=1")
    {
        echo "\nquerying names ($start, $limit)\n";
        $result = $this->mysqli->query("SELECT he.*, n.string, cf.string canonical_form FROM hierarchy_entries he LEFT JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (he.name_id=n.id) WHERE he.id  BETWEEN $start AND ".($start+$limit)." AND $filter");
        echo "done querying names\n";
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['id'];
            $rank_id = $row['rank_id'];
            $parent_id = $row['parent_id'];
            
            $this->objects[$id]['parent_id'] = $row['parent_id'];
            $this->objects[$id]['taxon_concept_id'] = $row['taxon_concept_id'];
            $this->objects[$id]['hierarchy_id'] = $row['hierarchy_id'];
            $this->objects[$id]['rank_id'] = $row['rank_id'];
            $this->objects[$id]['vetted_id'] = $row['vetted_id'];
            $this->objects[$id]['published'] = $row['published'];
            $this->objects[$id]['name'] = SolrApi::text_filter($row['string']);
            $this->objects[$id]['canonical_form'] = SolrApi::text_filter($row['canonical_form']);
            $this->objects[$id]['canonical_form_string'] = SolrApi::text_filter($row['canonical_form']);
        }
    }
    
    private function lookup_ancestries()
    {
        echo "\nlooking up ancestries\n";
        
        $ids_to_lookup = array();
        if(@$this->objects)
        {
            foreach($this->objects as $id => $junk)
            {
                $ids_to_lookup[$id] = 1;
            }
        }
        
        while($ids_to_lookup)
        {
            $result = $this->mysqli->query("SELECT he.id, he.parent_id, he.rank_id, n.string, cf.string canonical_form FROM hierarchy_entries he LEFT JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (he.name_id=n.id) WHERE he.id IN (". implode(",", array_keys($ids_to_lookup)) .")");
            $ids_to_lookup = array();
            while($result && $row=$result->fetch_assoc())
            {
                if($row['canonical_form']) $string = $row['canonical_form'];
                else $string = $row['string'];
                
                $this->node_metadata[$row['id']] = array('parent_id' => $row['parent_id'], 'rank_id' => $row['rank_id'], 'string' => $string);
                if($row['parent_id']) $ids_to_lookup[$row['parent_id']] = 1;
            }
        }
        
        echo "done looking up ancestries\n";
        
        if(@$this->objects)
        {
            foreach($this->objects as $id => $junk)
            {
                if(@$this->node_metadata[$id]['parent_id'] && $ancestry = $this->get_ancestry($this->node_metadata[$id]['parent_id']))
                {
                    foreach($ancestry as $rank => $name)
                    {
                        $this->objects[$id][$rank] = SolrApi::text_filter($name);
                    }
                }
            }
        }
    }
    
    private function get_ancestry($id)
    {
        if(isset($this->ancestries[$id])) return $this->ancestries[$id];
        
        $ancestry = array();
        if(@$this->node_metadata[$id])
        {
            if($this->node_metadata[$id]['parent_id'])
            {
                $ancestry = $this->get_ancestry($this->node_metadata[$id]['parent_id']);
            }
            
            if(@$this->rank_labels[$this->node_metadata[$id]['rank_id']])
            {
                $ancestry[$this->rank_labels[$this->node_metadata[$id]['rank_id']]] = $this->node_metadata[$id]['string'];
            }
        }
        
        $this->ancestries[$id] = $ancestry;
        return $ancestry;
    }
    
    private function lookup_synonyms($start, $limit, $filter = "1=1")
    {
        $sci = Language::find('scientific name');
        $common_name_id = SynonymRelation::insert('common name');
        
        echo "\nquerying synonyms\n";
        $result = $this->mysqli->query("SELECT s.*, n.string, cf.string canonical_form FROM synonyms s JOIN hierarchy_entries he ON (s.hierarchy_entry_id=he.id) JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (s.name_id=n.id) WHERE he.id BETWEEN $start AND ".($start+$limit)." AND $filter");
        echo "done querying synonyms\n";
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['hierarchy_entry_id'];
            $relation_id = $row['synonym_relation_id'];
            
            if(($row['language_id'] && $row['language_id'] != $sci) || $relation_id == $common_name_id) $field = 'common_name';
            else $field = 'synonym';
            
            $this->objects[$id][$field][SolrApi::text_filter($row['string'])] = 1;
            
            if($field == 'synonym' && $row['canonical_form'])
            {
                $this->objects[$id]['synonym_canonical'][SolrApi::text_filter($row['canonical_form'])] = 1;
            }
        }
    }
}

?>