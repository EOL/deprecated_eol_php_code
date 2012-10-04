<?php
namespace php_active_record;

class TaxonConceptIndexer
{
    private $mysqli;
    private $mysqli_slave;
    private $solr;
    private $objects;
    private $solr_server;
    
    public function __construct($solr_server = SOLR_SERVER)
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->solr_server = $solr_server;
    }
    
    public function index($hierarchy_id = NULL, $optimize = true)
    {
        $this->hierarchy_id = $hierarchy_id;
        $filter = "1=1";
        
        if($this->hierarchy_id)
        {
            $this->solr = new SolrAPI($this->solr_server, 'taxon_concepts');
            //$this->solr->delete("hierarchy_id:$this->hierarchy_id");
            //$filter = "he.hierarchy_id IN ($this->hierarchy_id)";
        }else
        {
            $this->solr = new SolrAPI($this->solr_server, 'taxon_concepts_swap');
            $this->solr->delete_all_documents();
        }
        
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc WHERE $filter");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            unset($this->objects);
            
            $this->lookup_names($i, $limit);
            $this->lookup_ranks($i, $limit);
            $this->lookup_top_images($i, $limit);
            $this->lookup_ancestors($i, $limit);
            
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        }
        
        
        if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        $this->solr->commit();
        if($optimize) $this->solr->optimize();
        
        if(!$hierarchy_id)
        {
            $results = $this->solr->get_results('taxon_concept_id:11518645');
            if($results) $this->solr->swap('taxon_concepts_swap', 'taxon_concepts');
        }
    }
    
    public function index_concepts(&$taxon_concept_ids = array(), $optimize = true)
    {
        $this->solr = new SolrAPI($this->solr_server, 'taxon_concepts');
        
        $batches = array_chunk($taxon_concept_ids, 10000);
        foreach($batches as $batch)
        {
            unset($this->objects);
            
            $this->lookup_names(null, null, null, $batch);
            $this->lookup_ranks(null, null, null, $batch);
            $this->lookup_top_images(null, null, null, $batch);
            $this->lookup_ancestors(null, null, null, $batch);
            
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        }
        
        $this->solr->commit();
        if($optimize) $this->solr->optimize();
    }
    
    function lookup_names($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        debug("querying names");
        $query = "SELECT tc.id, tc.vetted_id, tcn.preferred, tcn.vern, tcn.language_id, tcn.source_hierarchy_entry_id, n.string, cf.string FROM taxon_concepts tc LEFT JOIN (taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id) LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (tc.id=tcn.taxon_concept_id) WHERE tc.supercedure_id=0 AND tc.published=1 AND tc.id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $vetted_id = $row[1];
            $preferred = $row[2];
            $vern = $row[3];
            $language_id = $row[4];
            $source_hierarchy_entry_id = $row[5];
            $string = $row[5];
            $canonical_form = $row[5];
            
            if($vern && $string)
            {
                $attr = 'common_name';
                if(isset($this->objects[$id][$attr][$string])) continue;
                $name = SolrApi::text_filter($string);
                
                if($name) $this->objects[$id][$attr][$name] = 1;
            }elseif($string)
            {
                if(isset($this->objects[$id]['preferred_scientific_name'][$string])) continue;
                $name = SolrApi::text_filter($string);
                
                if($preferred)
                {
                    if($name) $this->objects[$id]['preferred_scientific_name'][$name] = 1;
                }
                
                if($name) $this->objects[$id]['scientific_name'][$name] = 1;
            }
            
            $this->objects[$id]['vetted_id'] = $vetted_id;
            $this->objects[$id]['published'] = 1;
            $this->objects[$id]['supercedure_id'] = 0;
        }
        
        // if any common name is also a scientific name - then remove the common name 
        foreach($this->objects as $id => $arr)
        {
            if(!isset($arr['common_name'])) continue;
            foreach($arr['common_name'] as $name => $val)
            {
                if(isset($this->objects[$id]['scientific_name'][$name]))
                {
                    unset($this->objects[$id]['common_name'][$name]);
                }
            }
        }
    }
    
    function lookup_ranks($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        debug("querying ranks");
        $query = " SELECT taxon_concept_id, rank_id, hierarchy_id FROM hierarchy_entries he WHERE he.visibility_id=".Visibility::find('visible')->id." AND he.published=1 AND taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $rank_id = $row[1];
            $hierarchy_id = $row[2];
            
            $this->objects[$id]['hierarchy_id'][$hierarchy_id] = 1;
            if($rank_id) $this->objects[$id]['rank_id'][$rank_id] = 1;
        }
    }
    
    function lookup_top_images($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        debug("querying top_images");
        $query = " SELECT ti.taxon_concept_id id, ti.data_object_id FROM top_concept_images ti JOIN data_objects do ON (ti.data_object_id=do.id) JOIN vetted v ON (do.vetted_id=v.id) WHERE ti.view_order=1 AND ti.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        $query .= " ORDER BY v.view_order ASC, do.data_rating DESC, do.id DESC";
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $data_object_id = $row[1];
            
            if(@!$this->objects[$id]['top_image_id'])
            {
                $this->objects[$id]['top_image_id'][$data_object_id] = 1;
            }
        }
    }
    
    function lookup_ancestors($start, $limit, $filter = "1=1", &$taxon_concept_ids = array())
    {
        debug("querying lookup_ancestors");
        $query = "SELECT taxon_concept_id id, ancestor_id FROM taxon_concepts_flattened tcf WHERE tcf.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $id = $row[0];
            $ancestor_id = $row[1];
            $this->objects[$id]['ancestor_taxon_concept_id'][$ancestor_id] = 1;
        }
    }
}

?>