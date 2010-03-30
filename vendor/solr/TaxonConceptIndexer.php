<?php

class TaxonConceptIndexer
{
    private $mysqli;
    private $solr;
    private $objects;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    public function index($hierarchy_id = NULL, $optimize = true)
    {
        $this->hierarchy_id = $hierarchy_id;
        $filter = "1=1";
        
        if($this->hierarchy_id)
        {
            $this->solr = new SolrAPI(SOLR_SERVER, 'taxon_concepts');
            //$this->solr->delete("hierarchy_id:$this->hierarchy_id");
            //$filter = "he.hierarchy_id IN ($this->hierarchy_id)";
        }else
        {
            $this->solr = new SolrAPI(SOLR_SERVER, 'taxon_concepts_swap');
            $this->solr->delete_all_documents();
        }
        
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc WHERE $filter");
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
            
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        }
        
        
        if(isset($this->objects)) $this->solr->send_attributes($this->objects);
        $this->solr->commit();
        if($optimize) $this->solr->optimize();
        
        if(!$hierarchy_id)
        {
            $this->solr->swap('taxon_concepts_swap', 'taxon_concepts');
        }
    }
    
    
    function lookup_names($start, $limit, $filter = "1=1")
    {
        echo "\nquerying names\n";
        $result = $this->mysqli->query("SELECT tc.id, tc.vetted_id, tcn.preferred, tcn.vern, tcn.language_id, n.string FROM taxon_concepts tc LEFT JOIN (taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id)) ON (tc.id = tcn.taxon_concept_id)  WHERE tc.id  BETWEEN $start AND  ".($start+$limit)." AND tc.supercedure_id=0 AND tc.published=1");
        echo "done querying names\n";
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['id'];
            $string = $row['string'];
            
            if($row['vern'] && $string)
            {
                $attr = 'common_name';
                //$name1 = SolrApi::text_filter($string, false);
                $name = SolrApi::text_filter($string);
                
                //if($name1) $this->objects[$id][$attr][$name1] = 1;
                if($name) $this->objects[$id][$attr][$name] = 1;
            }elseif($string)
            {
                //$name1 = SolrApi::text_filter($string, false);
                $name = SolrApi::text_filter($string);
                
                if($row['preferred'])
                {
                    //if($name1) $this->objects[$id]['preferred_scientific_name'][$name1] = 1;
                    if($name) $this->objects[$id]['preferred_scientific_name'][$name] = 1;
                }
                
                //if($name1) $this->objects[$id]['scientific_name'][$name1] = 1;
                if($name) $this->objects[$id]['scientific_name'][$name] = 1;
            }
            
            $this->objects[$id]['vetted_id'] = $row['vetted_id'];
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
                    //echo "$id) $name\n";
                    unset($this->objects[$id]['common_name'][$name]);
                }
            }
        }
    }
    
    function lookup_ranks($start, $limit, $filter = "1=1")
    {
        echo "\nquerying ranks\n";
        $result = $this->mysqli->query("SELECT  taxon_concept_id, rank_id, hierarchy_id FROM  hierarchy_entries WHERE taxon_concept_id BETWEEN $start AND ".($start+$limit)." AND tc.supercedure_id=0 AND tc.published=1");
        echo "done querying ranks\n";
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['taxon_concept_id'];
            $rank_id = $row['rank_id'];
            $hierarchy_id = $row['hierarchy_id'];
            
            $this->objects[$id]['hierarchy_id'][$hierarchy_id] = 1;
            
            if($rank_id)
            {
                $this->objects[$id]['rank_id'][$rank_id] = 1;
            }
        }
    }
    
    function lookup_top_images($start, $limit, $filter = "1=1")
    {
        echo "\nquerying top_images\n";
        $result = $this->mysqli->query("SELECT ti.taxon_concept_id id, ti.data_object_id FROM top_concept_images ti JOIN data_objects do ON (ti.data_object_id=do.id) JOIN vetted v ON (do.vetted_id=v.id) WHERE ti.taxon_concept_id BETWEEN $start AND ".($start+$limit)." AND tc.supercedure_id=0 AND tc.published=1 AND ti.view_order=1 ORDER BY v.view_order ASC, do.data_rating DESC, do.id DESC");
        echo "done querying top_images\n";
        
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row['id'];
            $data_object_id = $row['data_object_id'];
            
            if(@!$this->objects[$id]['top_image_id'])
            {
                $this->objects[$id]['top_image_id'][$data_object_id] = 1;
            }
        }
    }
}

?>