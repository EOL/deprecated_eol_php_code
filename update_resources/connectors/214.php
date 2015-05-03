<?php
namespace php_active_record;
/* connector for Vimeo 
estimated execution time: 46 minutes
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/VimeoAPI');

$resource_id = 214;
$taxa = VimeoAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = fopen($resource_path, "w")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
  return;
}
fwrite($OUT, $xml);
fclose($OUT);

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 1000)
{
    Functions::set_resource_status_to_force_harvest($resource_id);
    $command_line = "gzip -c " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml >" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml.gz";
    $output = shell_exec($command_line);
}

$elapsed_time_sec = microtime(1) - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "\n\n Done processing.";
?>