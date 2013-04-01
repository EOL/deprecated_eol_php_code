<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");

$collection_id = @$argv[1];
$confirmed = @$argv[2];

if(!$collection_id || !is_numeric($collection_id) || ($confirmed && ($confirmed != 'confirmed' && substr($confirmed, 0, 8) != 'ENV_NAME')))
{
    echo "\n\n\treindex_solr_collection.php [collection_id] [confirmed]\n\n";
    exit;
}

$collection = Collection::find($collection_id);
if(!$collection->id)
{
    echo "\n\nInvalid Collection ID\n";
    exit;
}

if($confirmed == 'confirmed')
{
    $indexer = new CollectionItemIndexer();
    $indexer->index_collection($collection->id);
    
    echo "\n\nDone\n\n";
}else
{
    echo "\n\n";
    echo "Reindexing:\n";
    print_r($collection);
    echo "\nCounts of objects to index:\n";
    $result = $GLOBALS['db_connection']->query("SELECT collected_item_type, COUNT(*) count FROM collection_items WHERE collection_id=$collection->id GROUP BY collected_item_type ORDER BY COUNT(*) DESC");
    while($result && $row=$result->fetch_assoc())
    {
        echo $row['collected_item_type'] . ": " . $row['count'] . "\n";
    }
    echo "\n\n\treindex_solr_collection.php [collection_id] [confirmed]\n\n";

}

?>
