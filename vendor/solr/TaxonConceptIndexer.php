<?php

class TaxonConceptIndexer
{
    private $mysqli;
    private $solr;
    private $objects;
    private $solr_server;
    
    public function __construct($solr_server = SOLR_SERVER)
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
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
            $results = $this->solr->get_results('taxon_concept_id:11518645');
            if($results) $this->solr->swap('taxon_concepts_swap', 'taxon_concepts');
        }
    }
    
    
    function lookup_names($start, $limit, $filter = "1=1")
    {
        echo "\nquerying names\n";
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, tc.vetted_id, tcn.preferred, tcn.vern, tcn.language_id, n.string FROM taxon_concepts tc LEFT JOIN (taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id)) ON (tc.id=tcn.taxon_concept_id) WHERE tc.id BETWEEN $start AND ".($start+$limit)." AND tc.supercedure_id=0 AND tc.published=1");
        echo "done querying names\n";
        
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                $parts = explode("\t", rtrim($line, "\n"));
                $id = $parts[0];
                $vetted_id = $parts[1];
                $preferred = $parts[2];
                $vern = $parts[3];
                $language_id = $parts[4];
                $string = $parts[5];
                
                if($vern && $string)
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
                    
                    if($preferred)
                    {
                        //if($name1) $this->objects[$id]['preferred_scientific_name'][$name1] = 1;
                        if($name) $this->objects[$id]['preferred_scientific_name'][$name] = 1;
                    }
                    
                    //if($name1) $this->objects[$id]['scientific_name'][$name1] = 1;
                    if($name) $this->objects[$id]['scientific_name'][$name] = 1;
                }
                
                $this->objects[$id]['vetted_id'] = $vetted_id;
                $this->objects[$id]['published'] = 1;
                $this->objects[$id]['supercedure_id'] = 0;
            }
        }
        fclose($RESULT);
        unlink($outfile);
        
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
    
    function lookup_ranks($start, $limit, $filter = "1=1")
    {
        echo "\nquerying ranks\n";
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT taxon_concept_id, rank_id, hierarchy_id FROM  hierarchy_entries WHERE taxon_concept_id BETWEEN $start AND ".($start+$limit));
        echo "done querying ranks\n";
        
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                $parts = explode("\t", rtrim($line, "\n"));
                $id = $parts[0];
                $rank_id = $parts[1];
                $hierarchy_id = $parts[2];
                
                $this->objects[$id]['hierarchy_id'][$hierarchy_id] = 1;
                if($rank_id) $this->objects[$id]['rank_id'][$rank_id] = 1;
            }
        }
        fclose($RESULT);
        unlink($outfile);
    }
    
    function lookup_top_images($start, $limit, $filter = "1=1")
    {
        echo "\nquerying top_images\n";
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT ti.taxon_concept_id id, ti.data_object_id FROM top_concept_images ti JOIN data_objects do ON (ti.data_object_id=do.id) JOIN vetted v ON (do.vetted_id=v.id) WHERE ti.taxon_concept_id BETWEEN $start AND ".($start+$limit)." AND ti.view_order=1 ORDER BY v.view_order ASC, do.data_rating DESC, do.id DESC");
        echo "done querying top_images\n";
        
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                $parts = explode("\t", rtrim($line, "\n"));
                $id = $parts[0];
                $data_object_id = $parts[1];
                
                if(@!$this->objects[$id]['top_image_id'])
                {
                    $this->objects[$id]['top_image_id'][$data_object_id] = 1;
                }
            }
        }
        fclose($RESULT);
        unlink($outfile);
    }
}

?>