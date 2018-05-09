<?php
namespace php_active_record;
/* connector for SPIRE
estimated execution time: 10 mins
Connector accesses the OWL (XML) files from remote server for most of the data and uses a spreadsheet for taxonomy info.
*/

echo "\nService no longer supported. See https://eol-jira.bibalex.org/browse/TRAM-719 \n";
return;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SpireAPI');
$timestart = time_elapsed();

$taxa = SpireAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "219.xml";
if(!($OUT = fopen($resource_path, "w")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
echo "\n\n Done processing.";
?>