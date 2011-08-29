<?php
namespace php_active_record;

require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

echo "okkkkkkkkkk\n";
print_r(Functions::remote_file_size('http://eolspecies.lifedesks.org/image/view/793'));

// if(@$_FILES['xml_upload']) $_POST['xml_upload'] = $_FILES['xml_upload'];
// $parameters =& $_GET;
// if(!$parameters) $parameters =& $_POST;
// 
// require_once("controllers/validator.php");
// 
// $validator_controller = new validator_controller();

?>