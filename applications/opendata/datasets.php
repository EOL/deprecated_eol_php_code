<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$ret = $_SERVER; // echo "<pre>"; print_r($ret); echo "</pre>";
header('Content-Type: application/json');
require_library('OpenData');
$func = new OpenData();
$func->get_datasets('p.state, p.metadata_modified DESC');
?>
