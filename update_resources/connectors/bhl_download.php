<?php
namespace php_active_record;
/* This will be an all-in-one BHL download facility. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
// ini_set('memory_limit','12096M'); //this can be removed and choose a caching solution. But let us try this first.
$timestart = time_elapsed();

$func = new BHL_Download_API();
$page_id = "52894701";
$page_id = "29882730";
$page_id = "47086838";
$func->GetPageMetadata($page_id);
?>