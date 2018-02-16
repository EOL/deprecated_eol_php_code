<?php
namespace php_active_record;
/* DATA-1590

taxon = 1266
dwc:ScientificName = 1266
reference = 18271
synonym = 0
commonName = 3473
dataObjects = 12150
reference = 0
texts = 10127
images = 2023
videos = 0
sounds = 0

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/STRIorigAPI');
$timestart = time_elapsed();

echo "\nUsed lifedesk_combine.php instead.\n";
return;

$params["eol_xml_file"] = "http://localhost/~eolit/cp/STRI/35_orig.xml.zip";
// $params["eol_xml_file"] = "https://dl.dropboxusercontent.com.zip";
$params["filename"]     = "35_orig.xml";
$params["dataset"]      = "STRI";
$params["resource_id"]  = 35;

$resource_id = $params["resource_id"];

$func = new STRIorigAPI($resource_id);
$xml = $func->process_xml($params);

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
    Functions::set_resource_status_to_harvest_requested($resource_id);
    Functions::gzip_resource_xml($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>