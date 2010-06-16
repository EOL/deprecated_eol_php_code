<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
require_vendor('wikipedia');

$result = $GLOBALS["db_connection"]->query("SELECT * FROM wikipedia_queue");
while($result && $row=$result->fetch_assoc())
{
    WikipediaHarvester::force_import($row['revision_id']);
    
    //echo "DELETE FROM wikipedia_queue WHERE id=". $row['id']."\n";
    $GLOBALS["db_connection"]->query("DELETE FROM wikipedia_queue WHERE id=". $row['id']);
}


?>