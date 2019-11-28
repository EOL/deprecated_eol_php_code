<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

/* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true;
*/

$params =& $_GET;
$task = $params['task'];
$ctrler = new \freshdata_controller(array());
require_once("show_build_status.php");
?>