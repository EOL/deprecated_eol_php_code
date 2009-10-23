#!/usr/local/bin/php
<?php
//#!/usr/local/bin/php
//exit;
set_time_limit(0);
ini_set('memory_limit','3500M');
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$query="update resources set accesspoint_url = 'http://pandanus.eol.org/public/EOL_resource/63.xml' where id = 63";
$result = $mysqli->query($query);    

?>