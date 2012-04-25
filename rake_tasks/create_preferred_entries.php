<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['db_connection'];

require_library('PreferredEntriesCalculator');

$calc = new PreferredEntriesCalculator();
$calc->begin_processing();


?>

