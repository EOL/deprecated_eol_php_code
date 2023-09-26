<?php
namespace php_active_record;
/* 
1st client: NMNHTypeRecordAPI_v2.php
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
require_library('connectors/DownloadExtractAPI');
$func = new DownloadExtractAPI();

// $params['url'] = "https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.72";
// $params['force_extension'] = 'zip'; //can be ommitted.
// $paths = $func->download_and_extract($params);

// $params['url'] = "http://localhost/cp_new/NMNH/type_specimen_resource/dwca-nmnh_extant_dwc-a-v1.8.zip";
$params['url'] = "http://localhost/eol_php_code//applications/content_server/resources_3/xeno_canto.tar.gz";
// $params['url'] = "http://localhost/eol_php_code//applications/content_server/resources_3/37.tar.gz";
// $params['url'] = "http://localhost/eol_php_code//applications/content_server/resources_3/sample.zip";
// $params['url'] = "http://localhost/eol_php_code//applications/content_server/resources_3/sample_no_folder.zip";

// $params['url'] = "https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.72";
// $params['force_extension'] = 'zip'; //can be ommitted.

$paths = $func->download_and_extract($params);
print_r($paths);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>