<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$mysqli =& $GLOBALS['mysqli_connection'];



Functions::log("Starting table_of_contents");

$mysqli->begin_transaction();

$mysqli->delete("DELETE dotoc FROM data_objects_table_of_contents dotoc JOIN data_objects_info_items doii USING (data_object_id)");
$mysqli->insert("INSERT IGNORE INTO data_objects_table_of_contents (SELECT doii.data_object_id, ii.toc_id FROM data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id = ii.id) WHERE ii.toc_id!=0)");

$mysqli->end_transaction();

Functions::log("Ended table_of_contents");

?>