<?php
namespace php_active_record;

/* connector for AquaMaps
estimated execution time: 3 hrs.
1st version: Maps are shown as embedded images in the Distribution text.
2nd version: Maps are shown in the Maps tab as image objects.
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AquamapsAPIv2');
$GLOBALS['ENV_DEBUG'] = false;
$taxa = AquamapsAPIv2::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$xml = str_replace("</dataObject>", "<additionalInformation><subtype>map</subtype></additionalInformation></dataObject>", $xml);
$resource_id = 123;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);
$elapsed_time_sec = microtime(1)-$timestart;

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600)
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
}

echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>