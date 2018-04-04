<?php
namespace php_active_record;

require_once(dirname(__FILE__) ."/../../config/environment.php");

$mysqli = $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = false;

if(@$_FILES['dwca_upload']) $_POST['dwca_upload'] = $_FILES['dwca_upload'];
$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;


if(isset($argv[2])) {
    $parameters = json_decode($argv[2], true);
    $parameters['f'] = 'index';
    echo "<pre>---"; print_r($parameters); echo "---</pre>"; //exit;
    echo "<hr>".$argv[1]."<hr>";
}



require_once("controllers/validator.php");

$validator_controller = new dwc_validator_controller();

?>