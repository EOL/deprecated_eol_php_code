<?php
namespace php_active_record;
/* connector for Avibase - IOC World Bird Names
estimated execution time: 3 minutes (9 hours with synonyms)
Connector scrapes the Avibase site.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AvibaseAPI');

$timestart = time_elapsed();

$av = new AvibaseAPI(355, 'ioc');
$av->get_all_taxa();

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "355.xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=355");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

?>