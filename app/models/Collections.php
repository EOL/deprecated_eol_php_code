<?php
namespace php_active_record;

class Collection extends ActiveRecord
{
    public static $belongs_to = array(
            array('user')
        );

    public function delete()
    {
        $collection_items_to_delete = array();
        $query = "SELECT id FROM collection_items WHERE collection_id=$this->id";
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row) $collection_items_to_delete[] = $row[0];
        $batches = array_chunk($collection_items_to_delete, 10000);
        foreach($batches as $batch)
        {
            // delete them from mysql
            $this->mysqli->delete("DELETE FROM collection_items WHERE id IN (". implode($batch, ",") .")");
            // remove them from solr
            $indexer = new CollectionItemIndexer();
            $indexer->index_collection_items($batch);
        }
        
        $this->mysqli->delete("DELETE FROM collections WHERE id = $this->id");
        $indexer = new SiteSearchIndexer();
        $indexer->index_collection($this->id);
    }

    public function set_item_count()
    {
        $this->mysqli->query("
            UPDATE collections c
            INNER JOIN (
                SELECT collection_id, count(*) AS num_items
                FROM collection_items
                WHERE collection_id = $this->id
            ) ci ON c.id = ci.collection_id
            SET collection_items_count = num_items WHERE collection_id = $this->id
        ");
    }
    
}

?>
