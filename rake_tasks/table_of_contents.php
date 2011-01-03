<?php

include_once(dirname(__FILE__) . "/../config/environment.php");

$log = HarvestProcessLog::create('Table of Contents');

$GLOBALS['db_connection']->begin_transaction();

$GLOBALS['db_connection']->delete("DELETE dotoc FROM data_objects_table_of_contents dotoc JOIN data_objects_info_items doii USING (data_object_id)");
$GLOBALS['db_connection']->insert("INSERT IGNORE INTO data_objects_table_of_contents (SELECT doii.data_object_id, ii.toc_id FROM data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id = ii.id) WHERE ii.toc_id!=0)");

$GLOBALS['db_connection']->end_transaction();

$log->finished();

?>