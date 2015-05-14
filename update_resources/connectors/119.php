<?php
namespace php_active_record;

/* connector for Photosynth
estimated execution time: 29 secs.

This connector will use an un-official service to search the Photosynth server.
It also scrapes the Photosynth site to get the tags entered by owners as the tags where not included in the service.
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PhotosynthAPI');
$GLOBALS['ENV_DEBUG'] = false;
$taxa = PhotosynthAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "119.xml";
if(!($OUT = fopen($resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);
$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
echo "\n\n Done processing.";
?>
