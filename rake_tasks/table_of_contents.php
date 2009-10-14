<?php

include_once(dirname(__FILE__) . "/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];



Functions::log("Starting table_of_contents");

$mysqli->begin_transaction();

$result = $mysqli->query("SELECT doii.data_object_id, ii.toc_id FROM data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id = ii.id) WHERE ii.toc_id!=0");
while($result && $row=$result->fetch_assoc())
{
    $mysqli->delete("DELETE FROM data_objects_table_of_contents WHERE data_object_id=".$row["data_object_id"]);
    $mysqli->insert("INSERT INTO data_objects_table_of_contents VALUES (".$row["data_object_id"].", ".$row["toc_id"].")");
}

$mysqli->end_transaction();

Functions::log("Ended table_of_contents");

?>