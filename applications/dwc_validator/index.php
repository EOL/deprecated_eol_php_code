<?php
namespace php_active_record;

require_once(dirname(__FILE__) ."/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
$mysqli = $GLOBALS['db_connection'];

if(@$_FILES['dwca_upload']) $_POST['dwca_upload'] = $_FILES['dwca_upload'];
$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;

require_once("controllers/validator.php");

$validator_controller = new dwc_validator_controller();

?>