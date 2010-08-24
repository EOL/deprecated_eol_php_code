<?php

class HierarchyEntryRelationshipIndexer
{
    private $mysqli;
    private $solr;
    private $objects;
    private $node_metadata;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    public function index($hierarchy_id = NULL, $optimize = true, $filter = "1=1")
    {
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, '')) return false;
        $this->solr = new SolrAPI(SOLR_SERVER, '');
         //        
         // $this->solr->delete("hierarchy_id_1:105");
         // $this->solr->delete("hierarchy_id_2:105");
         // 
         // return;
        $this->solr->delete_all_documents();
        
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
            $this->lookup_relatipnships($i, $limit);
        }
        $this->solr->commit();
        if($optimize) $this->solr->optimize();
    }
    
    
    private function lookup_relatipnships($start, $limit, $filter = "1=1")
    {
        echo "\nquerying names ($start, $limit)\n";
        $outfile = $this->mysqli->select_into_outfile("SELECT he1.id id1, he1.taxon_concept_id taxon_concept_id1, he1.hierarchy_id hierarchy_id1, he1.visibility_id visibility_id1, he2.id id2, he2.taxon_concept_id taxon_concept_id2, he2.hierarchy_id hierarchy_id2, he2.visibility_id visibility_id2, hr.relationship, hr.score FROM hierarchy_entry_relationships hr JOIN hierarchy_entries he1 ON (hr.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (hr.hierarchy_entry_id_2=he2.id) WHERE he1.id BETWEEN $start AND ".($start+$limit)." AND $filter");
        echo "done querying names\n";
        
        if(filesize($outfile)) $this->solr->send_from_mysql_result($outfile);
        unlink($outfile);
    }
}

?>