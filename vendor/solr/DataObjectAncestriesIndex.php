<?php

class DataObjectAncestriesIndexer
{
    private $mysqli;
    private $solr;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    public function index()
    {
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'data_objects')) return false;
        $this->solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        
        $this->solr->delete_all_documents();
        
        $start = 0;
        $max_id = 0;
        $limit = 30000;
        $result = $this->mysqli->query("SELECT MIN(data_object_id) min, MAX(data_object_id) max FROM data_objects_taxon_concepts");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        for($i=$start ; $i<=$max_id ; $i+=$limit)
        {
            unset($this->objects);
            
            $this->lookup_objects($i, $limit);
            $this->lookup_ancestries($i, $limit);
            
            if(isset($this->objects)) $this->solr->send_attributes($this->objects);
            //break;
        }
        $this->solr->optimize();
    }
    
    
    private function lookup_objects($start, $limit)
    {
        echo "\nquerying objects ($start, $limit)\n";
        $outfile = $this->mysqli->select_into_outfile("SELECT id, guid, data_type_id, vetted_id, visibility_id, published, data_rating, UNIX_TIMESTAMP(created_at), description FROM data_objects WHERE id BETWEEN $start AND ".($start+$limit)." AND (published=1 OR visibility_id!=".Visibility::find('visible').")");
        echo "done querying objects\n";
        
        $last_data_object_id = 0;
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                $line = rtrim($line, "\n");
                if(preg_match("/^([0-9]+)\t([0-9a-z]{32})\t([0-9])\t([0-9])\t([0-9])\t([0-9])\t(.*?)\t(.*?)\t(.*)$/ims", $line, $parts))
                {
                    $id = $parts[1];
                    $guid = $parts[2];
                    $data_type_id = $parts[3];
                    $vetted_id = $parts[4];
                    $visibility_id = $parts[5];
                    $published = $parts[6];
                    $data_rating = $parts[7];
                    $created_at = $parts[8];
                    $description = str_replace("|", " ", SolrApi::text_filter($parts[9]));
                    
                    $this->objects[$id]['guid'] = $guid;
                    $this->objects[$id]['data_type_id'] = $data_type_id;
                    $this->objects[$id]['vetted_id'] = $vetted_id;
                    $this->objects[$id]['visibility_id'] = $visibility_id;
                    $this->objects[$id]['published'] = $published;
                    $this->objects[$id]['data_rating'] = $data_rating;
                    $this->objects[$id]['created_at'] = date('Y-m-d', $created_at) . "T". date('h:i:s', $created_at) ."Z";
                    //$this->objects[$id]['description'] = str_replace("|", " ", $description);
                    
                    $last_data_object_id = $id;
                }
                
                // this would be a partial line. DataObjects can contain newlines and MySQL SELECT INTO OUTFILE
                // does not escape them so one object can span many lines
                elseif($last_data_object_id && !preg_match("/^([0-9]+)\t([0-9a-z]{32})\t/", $line))
                {
                    //echo $last_data_object_id."\n";
                    //echo "$line\n\n";
                    //$this->objects[$last_data_object_id]['description'] .= SolrApi::text_filter($line);
                }
            }
        }
        fclose($RESULT);
        unlink($outfile);
    }
    
    private function lookup_ancestries($start, $limit)
    {
        echo "\nquerying ancestries ($start, $limit)\n";
        $outfile = $this->mysqli->select_into_outfile("SELECT do.id, dotc.taxon_concept_id, tcf.ancestor_id FROM data_objects do LEFT JOIN (data_objects_taxon_concepts dotc LEFT JOIN taxon_concepts_flattened tcf ON (dotc.taxon_concept_id=tcf.taxon_concept_id)) ON (do.id=dotc.data_object_id) WHERE do.id BETWEEN $start AND ".($start+$limit)." AND (do.published=1 OR do.visibility_id!=".Visibility::find('visible').")");
        echo "done querying ancestries\n";
        
        $RESULT = fopen($outfile, "r");
        while(!feof($RESULT))
        {
            if($line = fgets($RESULT, 4096))
            {
                $parts = explode("\t", rtrim($line, "\n"));
                $id = $parts[0];
                $taxon_concept_id = $parts[1];
                $ancestor_id = $parts[2];
                
                if($taxon_concept_id) $this->objects[$id]['ancestor_id'][$taxon_concept_id] = 1;
                if($ancestor_id) $this->objects[$id]['ancestor_id'][$ancestor_id] = 1;
            }
        }
        fclose($RESULT);
        unlink($outfile);
    }
}

?>