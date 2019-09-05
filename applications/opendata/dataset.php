<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$ret = $_SERVER; // echo "<pre>"; print_r($ret); echo "</pre>";
if($GLOBALS['ENV_DEBUG'] == false) header('Content-Type: application/json');
require_library('OpenData');
$func = new OpenData();
$info = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);

// echo "<pre>"; print_r($ret); echo "</pre>"; exit;
// echo "<pre>"; print_r($info); echo "</pre>"; exit;

if($info['task'] == 'get resources') $func->get_resources_from_dataset($info);
else                                 $func->get_dataset_by_id($info['id']);
?>
