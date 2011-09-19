<?php
namespace php_active_record;

class HierarchyEntryRelationshipIndexer
{
    private $mysqli;
    private $solr;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    public function index($hierarchy = null, $compare_to_hierarchy = null)
    {
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entry_relationship')) return false;
        $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
        
        if($compare_to_hierarchy)
        {
            echo("deleting (hierarchy_id_1:$hierarchy->id AND hierarchy_id_2:$compare_to_hierarchy->id) OR (hierarchy_id_2:$hierarchy->id AND hierarchy_id_1:$compare_to_hierarchy->id)\n");
            $this->solr->delete("(hierarchy_id_1:$hierarchy->id AND hierarchy_id_2:$compare_to_hierarchy->id) OR (hierarchy_id_2:$hierarchy->id AND hierarchy_id_1:$compare_to_hierarchy->id)");
        }elseif($hierarchy)
        {
            echo("deleting hierarchy_id_1:$hierarchy->id OR hierarchy_id_2:$hierarchy->id\n");
            $this->solr->delete("hierarchy_id_1:$hierarchy->id OR hierarchy_id_2:$hierarchy->id");
        }
        
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        $result = $this->mysqli->query("SELECT MAX(id) as max FROM he_relations_tmp");
        if($result && $row=$result->fetch_assoc())
        {
            $max_id = $row["max"];
        }
        for($i=$start ; $i<=$max_id ; $i+=$limit)
        {
            $this->lookup_relatipnships($i, $limit);
        }
        //$this->solr->optimize();
    }
    
    
    private function lookup_relatipnships($start, $limit)
    {
        echo("querying relationships ($start, $limit)\n");
        $outfile = $this->mysqli->select_into_outfile("SELECT he1.id id1, he1.taxon_concept_id taxon_concept_id1, he1.hierarchy_id hierarchy_id1, he1.visibility_id visibility_id1, he2.id id2, he2.taxon_concept_id taxon_concept_id2, he2.hierarchy_id hierarchy_id2, he2.visibility_id visibility_id2, he1.taxon_concept_id=he2.taxon_concept_id same_concept, hr.relationship, hr.score FROM he_relations_tmp hr JOIN hierarchy_entries he1 ON (hr.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (hr.hierarchy_entry_id_2=he2.id) WHERE hr.id BETWEEN $start AND ".($start+$limit));
        echo("done querying relationships\n");
        
        if(filesize($outfile)) $this->solr->send_from_mysql_result($outfile);
        unlink($outfile);
    }
}

?>