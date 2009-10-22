#!/usr/local/bin/php
<?php
//#!/usr/local/bin/php
//connector for WORMS
//exit;
/* 
22966. 22964 of 68984
*/
set_time_limit(0);
ini_set('memory_limit','3500M');
//define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$query="update resources set accesspoint_url = 'http://pandanus.eol.org/public/EOL_resource/35.xml' where id = 35";
$result = $mysqli->query($query);    

?>