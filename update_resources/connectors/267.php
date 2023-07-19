<?php
namespace php_active_record;
/* connector for CalPhotos EoL XML resource -- https://eol-jira.bibalex.org/browse/DATA-1618
execution time: 

CalPhotos (ID = 267)
Numbers from the partners XML:
taxa	images
24293	173635	(last harvest)
25752	190186	(next harvest)

267	Tue 2023-07-18 11:27:30 AM	{"agent.tab":518, "media_resource.tab":368069, "taxon.tab":37015, "time_elapsed":false}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/INBioAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 267;

$xml_resource = "http://calphotos.berkeley.edu/eol.xml.gz";
// $xml_resource = "http://localhost/cp/CalPhotos/eol.xml.gz"; //local debug only

$func = new INBioAPI();
$info = $func->extract_archive_file($xml_resource, "eol.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*5)); //expires in 5 days 432000
if(!$info) return;
print_r($info);
$temp_dir = $info['temp_dir'];

$xml_string = Functions::get_remote_file($temp_dir . "eol.xml");
$xml_string = Functions::remove_invalid_bytes_in_XML($xml_string);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($WRITE = Functions::file_open($resource_path, "w"))) return;
fwrite($WRITE, $xml_string);
fclose($WRITE);

// remove tmp dir
if($temp_dir) shell_exec("rm -fr $temp_dir");

// Functions::gzip_resource_xml($resource_id); no longer needed as it will be converted to DwC-A
Functions::set_resource_status_to_harvest_requested($resource_id);

//start convert EOL XML to EOL DwCA
require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "CalPhotos", false); //false means not NMNH resource

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>
