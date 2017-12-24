<?php
namespace php_active_record;
/* connector for DATA-1718.php
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$timestart = time_elapsed();

/*
$url1 = "http://www.eol.org/files/pdfs/mou/EOL_ToL-mou.pdf";
$url2 = "/Library/WebServer/Documents/eol_php_code/tmp/tmp_72243.file.pdf";
echo "\n$url1 - ".pathinfo($url1, PATHINFO_FILENAME);
echo "\n$url2 - ".pathinfo($url2, PATHINFO_FILENAME);
exit("\n");
*/

require_library('connectors/EOLv2MetadataAPI');
$func = new EOLv2MetadataAPI();
$func->start_partner_metadata();
// $func->save_all_MOUs();
// $func->start_resource_metadata();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>