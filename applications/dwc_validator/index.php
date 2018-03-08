<?php
namespace php_active_record;

echo "<hr>test 01<hr>";

require_once(dirname(__FILE__) ."/../../config/environment.php");

echo "<hr>test 02<hr>";

$mysqli = $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = true;

if(@$_FILES['dwca_upload']) $_POST['dwca_upload'] = $_FILES['dwca_upload'];
$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;

require_once("controllers/validator.php");

$validator_controller = new dwc_validator_controller();

?>