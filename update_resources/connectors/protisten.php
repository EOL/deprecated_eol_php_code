<?php
namespace php_active_record;
/* Protisten.de gallery - https://eol-jira.bibalex.org/browse/DATA-1802
protisten	Thursday 2019-03-21 11:05:34 AM	{"agent.tab":1,"media_resource.tab":1587,"taxon.tab":556}
protisten	Thursday 2019-03-28 09:40:16 PM	{"agent.tab":1,"media_resource.tab":1587,"taxon.tab":556}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Protisten_deAPI');
$timestart = time_elapsed();
$resource_id = "protisten";
$func = new Protisten_deAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete working resource folder
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>