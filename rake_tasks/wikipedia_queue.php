<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
require_vendor('wikipedia');

$result = $GLOBALS["db_connection"]->query("SELECT * FROM wikipedia_queue WHERE harvested_at IS NULL");
while($result && $row=$result->fetch_assoc())
{
    // echo "Attempting to import: ".$row['revision_id']."\n";
    $success = WikipediaHarvester::force_import($row['revision_id']);
    
    $GLOBALS["db_connection"]->query("UPDATE wikipedia_queue SET harvested_at = NOW() WHERE id=". $row['id']);
    if($success) $GLOBALS["db_connection"]->query("UPDATE wikipedia_queue SET harvest_succeeded = 1 WHERE id=". $row['id']);
}


?>