<?php
namespace php_active_record;
/* connector for DATA-1718.php
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$timestart = time_elapsed();

require_library('connectors/EOLv2MetadataAPI');
$func = new EOLv2MetadataAPI();
$func->start();


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>