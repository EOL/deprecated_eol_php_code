<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$ret = $_SERVER; // echo "<pre>"; print_r($ret); echo "</pre>";
if($GLOBALS['ENV_DEBUG'] == false) header('Content-Type: application/json');
require_library('OpenData');
$func = new OpenData();
$dataset_id = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);
$func->get_dataset_by_id($dataset_id);
?>
