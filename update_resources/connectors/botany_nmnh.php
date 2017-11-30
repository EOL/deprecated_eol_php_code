<?php
namespace php_active_record;
/*  */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHBotanyAPI');
$GLOBALS['ENV_DEBUG'] = true;
// ini_set('memory_limit','4096M'); //314,5728,000
$timestart = time_elapsed();
$resource_id = 346;

//test resources - works OK
$params["eol_xml_file"] = 'http://localhost/eol_php_code/applications/content_server/resources/eli.xml';
$params["filename"] = 'eli.xml';
$params["xmlYN"] = true;

// $params["eol_xml_file"] = 'http://localhost/eol_php_code/applications/content_server/resources/eli.xml.zip';
// $params["filename"] = 'eli.xml';
// $params["xmlYN"] = false;

//actual resource - works OK
// /*
$params["eol_xml_file"] = 'http://collections.mnh.si.edu/services/eol/nmnh-botany-response.xml.gz';
$params["eol_xml_file"] = 'http://localhost/cp/OpenData/EOLxml_2_DWCA/nmnh-botany-response.xml.gz';
$params["filename"] = 'nmnh-botany-response.xml';
$params["xmlYN"] = false;
// */

$func = new NMNHBotanyAPI($resource_id);
$func->start($params);

// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>