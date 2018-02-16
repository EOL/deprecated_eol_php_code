<?php
namespace php_active_record;
/* connector for Ecomare - Dutch Marine and Coastal Species Encyclopedia
estimated execution time: 18 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nPartner site no longer available. Used collections_generic.php instead.\n";
return;

$timestart = time_elapsed();
require_library('connectors/EcomareAPI');
$resource_id = 414;

$func = new EcomareAPI();
$taxa = $func->get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = fopen($resource_path, "w")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_harvest_requested($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>