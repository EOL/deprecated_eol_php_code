<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

$mysqli->update("UPDATE site_configuration_options SET value = NULL WHERE parameter='global_site_warning'");

?>
