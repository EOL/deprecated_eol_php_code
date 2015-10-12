<?php
namespace php_active_record;

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
        if($r = Rank::find_or_create_by_translated_label('kingdom')) $this->rank_labels[$r->id] = 'kingdom';
        if($r = Rank::find_or_create_by_translated_label('regn.')) $this->rank_labels[$r->id] = 'kingdom';
        if($r = Rank::find_or_create_by_translated_label('phylum')) $this->rank_labels[$r->id] = 'phylum';
        if($r = Rank::find_or_create_by_translated_label('phyl.')) $this->rank_labels[$r->id] = 'phylum';
        if($r = Rank::find_or_create_by_translated_label('class')) $this->rank_labels[$r->id] = 'class';
        if($r = Rank::find_or_create_by_translated_label('cl.')) $this->rank_labels[$r->id] = 'class';
        if($r = Rank::find_or_create_by_translated_label('order')) $this->rank_labels[$r->id] = 'order';
        if($r = Rank::find_or_create_by_translated_label('ord.')) $this->rank_labels[$r->id] = 'order';
        if($r = Rank::find_or_create_by_translated_label('family')) $this->rank_labels[$r->id] = 'family';
        if($r = Rank::find_or_create_by_translated_label('fam.')) $this->rank_labels[$r->id] = 'family';
        //if($r = Rank::find_or_create_by_translated_label('f.')) $this->rank_labels[$r->id] = 'family';
        if($r = Rank::find_or_create_by_translated_label('genus')) $this->rank_labels[$r->id] = 'genus';
        if($r = Rank::find_or_create_by_translated_label('gen.')) $this->rank_labels[$r->id] = 'genus';
        if($r = Rank::find_or_create_by_translated_label('species')) $this->rank_labels[$r->id] = 'species';
        if($r = Rank::find_or_create_by_translated_label('sp.')) $this->rank_labels[$r->id] = 'species';
    }
    
    public function index($hierarchy_id = NULL, $optimize = true)
    {
        $filter = "1=1";
        
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entries')) {
        	debug("SOLR SERVER not defined or can't ping hierarchy_entries!");
        	return false;
        }
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
        if($start == 0)
        {
          debug("No entries to index in ($hierarchy_id)");
          return;
        } 

        $count = $max_id-$start+1;
        debug("indexing $count entries in solr");
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
       $solr_count = $this->solr->count_results("hierarchy_id:$hierarchy_id");
       debug("indexed in SOLR: $solr_count entries");
       if ($count == $solr_count)
       {
         debug("All hierarchy entries were correctly indexed in SOLR");
       }else 
       {
          debug("WARNING:: Not all hierarchy entries are present in SOLR!");
       }
    }
    
    
    private function lookup_names($start, $limit, $filter = "1=1")
    {
        if($GLOBALS['ENV_DEBUG']) echo("querying names ($start, $limit)\n");
        $result = $this->mysqli->query("SELECT he.*, n.string, rcf.string canonical_form FROM hierarchy_entries he LEFT JOIN (names n LEFT JOIN canonical_forms rcf ON (n.ranked_canonical_form_id=rcf.id)) ON (he.name_id=n.id) WHERE he.id  BETWEEN $start AND ".($start+$limit)." AND $filter");
        if($GLOBALS['ENV_DEBUG']) echo("done querying names\n");
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['id'];
            $rank_id = $row['rank_id'];
            $parent_id = $row['parent_id'];
            $canonical_form = $row['canonical_form'];
            
            $this->objects[$id]['parent_id'] = $row['parent_id'];
            $this->objects[$id]['taxon_concept_id'] = $row['taxon_concept_id'];
            $this->objects[$id]['hierarchy_id'] = $row['hierarchy_id'];
            $this->objects[$id]['rank_id'] = $row['rank_id'];
            $this->objects[$id]['vetted_id'] = $row['vetted_id'];
            $this->objects[$id]['published'] = $row['published'];
            $this->objects[$id]['name'] = SolrApi::text_filter($row['string']);
            if($canonical_form)
            {
                if(preg_match("/^(.* sp)\.?( |$)/", $canonical_form, $arr)) $canonical_form = $arr[1];
                else
                {
                    while(preg_match("/ (var|convar|subsp|ssp|cf|f|f\.sp|c|\*)\.?( |$)/", $canonical_form))
                    {
                        $canonical_form = preg_replace("/ (var|convar|subsp|ssp|cf|f|f\.sp|c|\*)\.?( |$)/", "\\2", $canonical_form);
                    }
                }
            }
            $this->objects[$id]['canonical_form'] = SolrApi::text_filter($canonical_form);
            $this->objects[$id]['canonical_form_string'] = $this->objects[$id]['canonical_form'];
        }
    }
    
    private function lookup_ancestries()
    {
        if($GLOBALS['ENV_DEBUG']) echo("looking up ancestries\n");
        
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
        
        if($GLOBALS['ENV_DEBUG']) echo("done looking up ancestries\n");
        
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
        $sci = Language::find_or_create_for_parser('scientific name')->id;
        $common_name_id = SynonymRelation::find_or_create_by_translated_label('common name')->id;
        
        if($GLOBALS['ENV_DEBUG']) echo("querying synonyms\n");
        $result = $this->mysqli->query("SELECT s.*, n.string, rcf.string canonical_form FROM synonyms s JOIN hierarchy_entries he ON (s.hierarchy_entry_id=he.id) JOIN (names n LEFT JOIN canonical_forms rcf ON (n.ranked_canonical_form_id=rcf.id)) ON (s.name_id=n.id) WHERE he.id BETWEEN $start AND ".($start+$limit)." AND $filter");
        if($GLOBALS['ENV_DEBUG']) echo("done querying synonyms\n");
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['hierarchy_entry_id'];
            $relation_id = $row['synonym_relation_id'];
            
            if(($row['language_id'] && $row['language_id'] != $sci) || $relation_id == $common_name_id) $field = 'common_name';
            else $field = 'synonym';
            
            $this->objects[$id][$field][SolrApi::text_filter($row['string'])] = 1;
            
            if($field == 'synonym' && $row['canonical_form'])
            {
                $canonical_form = $row['canonical_form'];
                if($canonical_form)
                {
                    if(preg_match("/^(.* sp)\.?( |$)/", $canonical_form, $arr)) $canonical_form = $arr[1];
                    else
                    {
                        while(preg_match("/ (var|convar|subsp|ssp|cf|f|f\.sp|c|\*)\.?( |$)/", $canonical_form))
                        {
                            $canonical_form = preg_replace("/ (var|convar|subsp|ssp|cf|f|f\.sp|c|\*)\.?( |$)/", "\\2", $canonical_form);
                        }
                    }
                }
                if($canonical_form) $this->objects[$id]['synonym_canonical'][SolrApi::text_filter($canonical_form)] = 1;
            }
        }
    }
}

?>
