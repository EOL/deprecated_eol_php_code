<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

$query = "UPDATE site_configuration_options
    SET value='EOL is scheduled for maintenance on ". date('l, F j') ." between 8:00 and 10:00 AM ". date('T') .".<br/>During this time you may find changes in EOL\'s performance and availability.'
    WHERE parameter='global_site_warning'";
$mysqli->update($query);

?>
