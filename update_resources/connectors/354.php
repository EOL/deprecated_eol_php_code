<?php
namespace php_active_record;
/* connector for Avibase - Howard & Moore
estimated execution time: 9 minutes (8.5 hours with synonyms)
Connector scrapes the Avibase site.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AvibaseAPI');

$timestart = time_elapsed();

$av = new AvibaseAPI(354, 'howardmoore');
$av->get_all_taxa();

// set to Harvest Requested
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "354.xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Harvest Requested')->id." WHERE id=354");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

?>