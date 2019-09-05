<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$ret = $_SERVER; // echo "<pre>"; print_r($ret); echo "</pre>";
header('Content-Type: application/json');
require_library('OpenData');
$func = new OpenData();
// $org_id = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);
$info   = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);

debug("<hr>$org_id<hr>");
$func->get_organization_by_id($info['id']);
?>
