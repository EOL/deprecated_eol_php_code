<?php
namespace php_active_record;

require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = false;

if(@$_FILES['xml_upload']) $_POST['xml_upload'] = $_FILES['xml_upload'];
$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;

require_once("controllers/validator.php");

$validator_controller = new validator_controller();

?>