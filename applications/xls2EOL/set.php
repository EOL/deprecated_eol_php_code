<?php
//use to set values in the database

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$query="update info_items set info_items.toc_id = 311, info_items.label = 'Keys' where info_items.schema_value = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Key'";
$update = $mysqli->query($query);

print "\n\n --end-- \n\n";
?>