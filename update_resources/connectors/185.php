<?php
namespace php_active_record;

/* connector for Turbellarian
Estimated execution time: 10.57 hrs.
This connector gets data from website. Ancestry information is still pending, to be provided by partner.

as of       records
2010 10 01  17492
2010 11 18  9780
2011 01 05  9491
2011 04 15  9508
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/TurbellarianAPI');

$taxa = TurbellarianAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_id = 185;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

// set Turbellarian to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>