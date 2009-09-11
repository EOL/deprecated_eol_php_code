#!/usr/local/bin/php
<?php

include_once(dirname(__FILE__) . "/../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$mysqli->begin_transaction();

$result = $mysqli->query("SELECT doii.data_object_id, ii.toc_id FROM data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id = ii.id) WHERE ii.toc_id!=0");
while($result && $row=$result->fetch_assoc())
{
    $mysqli->delete("DELETE FROM data_objects_table_of_contents WHERE data_object_id=".$row["data_object_id"]);
    $mysqli->insert("INSERT INTO data_objects_table_of_contents VALUES (".$row["data_object_id"].", ".$row["toc_id"].")");
}


// $mysqli_wattle = load_mysql_environment("wattle");
// // Temporary fix for those DOs without info items (legacy data)
// $result = $mysqli_wattle->query("select distinct dataObjectID, tocNodeID from eolData_demo.eolText");
// while($result && $row=$result->fetch_assoc())
// {
//     $mysqli->insert("INSERT INTO data_objects_table_of_contents VALUES (".$row["dataObjectID"].", ".$row["tocNodeID"].")");
// }

$mysqli->end_transaction();

?>