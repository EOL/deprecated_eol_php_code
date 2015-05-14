<?php
exit;
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['db_connection'];


$cf = new CollectionsFixer();
$cf->get_collection_counts();


class CollectionsFixer
{
    private $mysqli;
    private $solr;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        $this->solr = new SolrAPI(SOLR_SERVER, 'collection_items');
        $this->collection_item_indexer = new CollectionItemIndexer();
    }
    
    public function get_collection_counts()
    {
        // $test_collection_id = 19;
        if(isset($test_collection_id))
        {
            $result = $this->mysqli->query("SELECT c.id, count(*) count FROM collections c JOIN collection_items ci ON (c.id=ci.collection_id) WHERE c.id=$test_collection_id GROUP BY c.id");
        }else
        {
            $result = $this->mysqli->query("SELECT c.id, count(*) count FROM collections c JOIN collection_items ci ON (c.id=ci.collection_id) GROUP BY c.id");
        }
        while($result && $row=$result->fetch_assoc())
        {
            $collection_id = $row['id'];
            $mysql_count = $row['count'];
            $response = $this->solr->query("collection_id:$collection_id&rows=1");
            $solr_count = $response->numFound;
            if($mysql_count == $solr_count && $mysql_count <= 2) continue;
            
            debug("get_collection_counts: $collection_id = $mysql_count");
            $this->fix_collection($collection_id, isset($test_collection_id));
        }
    }
    
    public function fix_collection($collection_id, $test_mode)
    {
        debug("CheckCollections::fix_collection ($collection_id)");
        $collection_ids_in_mysql = array();
        foreach($this->mysqli->iterate_file("SELECT id, object_id FROM collection_items WHERE collection_id=$collection_id ORDER BY id") as $row_num => $row)
        {
            $collection_ids_in_mysql[] = $row[0] .":". $row[1];
        }
        
        $collection_ids_in_solr = array();
        $solr = new SolrAPI(SOLR_SERVER, 'collection_items');
        $response = $this->solr->query("collection_id:$collection_id&rows=1");
        if(!isset($response->numFound))
        {
            debug("****** THERE ARE NO RESULTS");
            return false;
        }
        $solr_count = $response->numFound;
        $batch_size = 100000;
        $number_of_batches = ceil($solr_count / $batch_size);
        for($i=0 ; $i<$number_of_batches ; $i++)
        {
            $response = $this->solr->query("collection_id:$collection_id&start=". ($i*$batch_size) ."&rows=$batch_size&fl=collection_item_id,object_id&sort=collection_item_id asc");
            if(!isset($response->numFound))
            {
                debug("****** THERE ARE NO RESULTS");
                return false;
            }
            foreach($response->docs as $doc)
            {
                $collection_ids_in_solr[] = $doc->collection_item_id .":". $doc->object_id;
            }
        }
        
        $items_that_need_to_be_added = array_diff($collection_ids_in_mysql, $collection_ids_in_solr);
        $items_that_need_to_be_deleted = array_diff($collection_ids_in_solr, $collection_ids_in_mysql);
        
        debug("Need to be Added: ". count($items_that_need_to_be_added));
        debug("Need to be Deleted: ". count($items_that_need_to_be_deleted) ."\n");
        
        if($test_mode) return;
        
        if($items_that_need_to_be_deleted)
        {
            foreach($items_that_need_to_be_deleted as &$item)
            {
                if(preg_match("/^([0-9]+):/", $item, $arr)) $item = $arr[1];
            }
        }
        $chunks = array_chunk($items_that_need_to_be_deleted, 10000);
        foreach($chunks as $chunk)
        {
            $delete_queries = array();
            foreach($chunk as $id) $delete_queries[] = "collection_item_id:$id";
            print_r($delete_queries);
            $this->solr->delete_by_queries($delete_queries, false);
            $this->solr->commit();
        }
        
        
        if($items_that_need_to_be_added)
        {
            foreach($items_that_need_to_be_added as &$item)
            {
                if(preg_match("/^([0-9]+):/", $item, $arr)) $item = $arr[1];
            }
        }
        $chunks = array_chunk($items_that_need_to_be_added, 10000);
        foreach($chunks as $chunk)
        {
            print_r($chunk);
            $this->collection_item_indexer->index_collection_items($chunk);
            $this->solr->commit();
        }
    }
}




?>
