<?php
namespace php_active_record;
/* connector for Learning + Education Group
Partner provides RSS feed.
estimated execution time: just a few seconds
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/LearningEducationAPI');

$taxa = LearningEducationAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "257_temp.xml";
if(!($OUT = fopen($resource_path, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);

if(filesize($resource_path) > 600)
{
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "257.xml", CONTENT_RESOURCE_LOCAL_PATH . "257_previous.xml");
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "257_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . "257.xml");
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Harvest Requested')->id." WHERE id=257");
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "\n\n Done processing.";
?>
