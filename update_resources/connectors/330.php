<?php
namespace php_active_record;
/* connector for CalPhotos - Moorea Biocode EoL XML resource -- https://eol-jira.bibalex.org/browse/DATA-1618
execution time: 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/INBioAPI');
$timestart = time_elapsed();
$resource_id = 330;

$xml_resource = "http://calphotos.berkeley.edu/eol_biocode.xml.gz";
$xml_resource = "http://localhost/cp/CalPhotos/eol_biocode.xml.gz";

$func = new INBioAPI();
$info = $func->extract_archive_file($xml_resource, "eol_biocode.xml");
if(!$info) return;
print_r($info);
$temp_dir = $info['temp_dir'];

$xml_string = Functions::get_remote_file($temp_dir . "eol_biocode.xml");
$xml_string = remove_invalid_bytes_in_XML($xml_string);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($WRITE = Functions::file_open($resource_path, "w"))) return;
fwrite($WRITE, $xml_string);
fclose($WRITE);

// remove tmp dir
if($temp_dir) shell_exec("rm -fr $temp_dir");

Functions::set_resource_status_to_force_harvest($resource_id);
Functions::gzip_resource_xml($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

function remove_invalid_bytes_in_XML($string)
{
    $string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
    return $string;
}

?>
