<?php

define('ENVIRONMENT', 'slave');
//define('MYSQL_DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];




$indexer = new HierarchyEntryIndexer();
$indexer->index();



?>