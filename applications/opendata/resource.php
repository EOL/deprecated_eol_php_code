<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

// $resource_id = @$_GET["resource_id"];
// if(!$resource_id) $function = @$_POST["resource_id"]; //not needed yet
// print_r(@$_GET);

$ret = $_SERVER; // echo "<pre>"; print_r($ret); echo "</pre>";
header('Content-Type: application/json');
require_library('OpenData');
$func = new OpenData();
$resource_id = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);

$info = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);
$func->get_resource_by_id($info['id']);
?>
