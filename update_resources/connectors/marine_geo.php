<?php
namespace php_active_record;
/*
https://docs.google.com/document/d/1a0KFu_tKTztDdBgTuPeaymZvHaFzOgTHSycwVB1CA7s/edit?userstoinvite=eagbayaninf@gmail.com&ts=5d1b6435
https://collections.nmnh.si.edu/ipt/
curl -L -o nmnh_extant_dwc-a-v1.25.zip -C - https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.25
curl -C - https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.25
curl -L -O -C - https://collections.nmnh.si.edu/ipt/archive.do?r=nmnh_extant_dwc-a&v=1.25
*/
/* how to run:
php update_resources/connectors/marine_geo.php _ input.xlsx
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// /*
$GLOBALS['ENV_DEBUG'] = false;
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
// */
ini_set('memory_limit','7096M');
require_library('connectors/MarineGEOAPI');
$timestart = time_elapsed();

$params['jenkins_or_cron']  = @$argv[1];
$params['filename']         = @$argv[2];
// print_r($params); exit;
/*Array(
    [jenkins_or_cron] => jenkins
    [filename] => 1574915471.zip
)*/
if($val = $params['filename']) $filename = $val;
else                           $filename = '';

$resource_id = 'Dep-01';
$func = new MarineGEOAPI($resource_id);

$func->start($filename);
// Functions::finalize_dwca_resource($resource_id, false, false); //3rd true means delete working dir

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>