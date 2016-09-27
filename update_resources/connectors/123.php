<?php
namespace php_active_record;

/* connector for AquaMaps
estimated execution time: 3 hrs.
1st version: Maps are shown as embedded images in the Distribution text.
2nd version: Maps are shown in the Maps tab as image objects.
*/
return; //aquamaps.org is down until further notice
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AquamapsAPIv2');
$timestart = time_elapsed();
$taxa = AquamapsAPIv2::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$xml = str_replace("</dataObject>", "<additionalInformation><subtype>map</subtype></additionalInformation></dataObject>", $xml);
$resource_id = 123;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = fopen($resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);
$elapsed_time_sec = time_elapsed() - $timestart;
Functions::set_resource_status_to_harvest_requested($resource_id);

echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>