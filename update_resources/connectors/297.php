<?php
namespace php_active_record;
/* connector for Iabin
Partner provides DWC-A file
estimated execution time: 0.76 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/IabinAPI');
$resource_id = 297; 
$taxa = IabinAPI::get_all_taxa($resource_id);

$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $xml);
fclose($OUT);

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
exit("\n\n Done processing.");
?>