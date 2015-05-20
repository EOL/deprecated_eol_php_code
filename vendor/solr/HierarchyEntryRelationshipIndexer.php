<?php
namespace php_active_record;

class HierarchyEntryRelationshipIndexer
{
    private $mysqli;
    private $solr;
    private $relations_table_name;
    
    public function __construct($relations_table_name = 'he_relations_tmp')
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->relations_table_name = $relations_table_name;
    }
    
    public function index($options = array())
    {
        if(!$options['hierarchy']) {
        	debug("Missing options['hierarchy']!");
        	return false;
        }
        if(get_class($options['hierarchy']) != 'php_active_record\Hierarchy') {
        	debug("options['hierarchy'] is not a valid php_active_record/Hierarchy!");
        	return false;
        }
        if($options['hierarchy_entry_ids'] && !is_array($options['hierarchy_entry_ids'])) {
        	debug("options['hierarchy_entry_ids'] must be an array!");
        	return false;
        }
        
        if(!defined('SOLR_SERVER') || !SolrAPI::ping(SOLR_SERVER, 'hierarchy_entry_relationship')) {
        	debug("SOLR SERVER not defined or can't ping hierarchy_entry_relationship");
        	return false;
        }
        $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entry_relationship');
        
        if($options['hierarchy_entry_ids'])
        {
            $batches = array_chunk($options['hierarchy_entry_ids'], 5000);
            foreach($batches as $batch)
            {
                $queries = array();
                foreach($batch as $id)
                {
                    $queries[] = "hierarchy_entry_id_1:$id";
                    $queries[] = "hierarchy_entry_id_2:$id";
                }
                $this->solr->delete_by_queries($queries);
            }
        }else
        {
            $hierarchy = $options['hierarchy'];
            if($GLOBALS['ENV_DEBUG']) echo("deleting hierarchy_id_1:$hierarchy->id OR hierarchy_id_2:$hierarchy->id\n");
            $this->solr->delete("hierarchy_id_1:$hierarchy->id OR hierarchy_id_2:$hierarchy->id");
        }
        
        
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        $result = $this->mysqli->query("SELECT MAX(id) as max FROM $this->relations_table_name");
        if($result && $row=$result->fetch_assoc())
        {
            $max_id = $row["max"];
        }
        for($i=$start ; $i<=$max_id ; $i+=$limit)
        {
            $this->lookup_relationships($i, $limit);
        }
        $this->solr->commit();
    }
    
    
    private function lookup_relationships($start, $limit)
    {
        if($GLOBALS['ENV_DEBUG']) echo("querying relationships ($start, $limit)\n");
        $outfile = $this->mysqli->select_into_outfile("SELECT he1.id id1, he1.taxon_concept_id taxon_concept_id1, he1.hierarchy_id hierarchy_id1, he1.visibility_id visibility_id1, he2.id id2, he2.taxon_concept_id taxon_concept_id2, he2.hierarchy_id hierarchy_id2, he2.visibility_id visibility_id2, he1.taxon_concept_id=he2.taxon_concept_id same_concept, hr.relationship, hr.score FROM $this->relations_table_name hr JOIN hierarchy_entries he1 ON (hr.hierarchy_entry_id_1=he1.id) JOIN hierarchy_entries he2 ON (hr.hierarchy_entry_id_2=he2.id) WHERE hr.id BETWEEN $start AND ".($start+$limit));
        if($GLOBALS['ENV_DEBUG']) echo("done querying relationships\n");
        
        if(filesize($outfile)) $this->solr->send_from_mysql_result($outfile);
        unlink($outfile);
    }
}

?>