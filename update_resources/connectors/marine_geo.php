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
php update_resources/connectors/marine_geo.php _ _ https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/input.xlsx eli173
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// /*
$GLOBALS['ENV_DEBUG'] = false;
// $GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
// */
ini_set('memory_limit','7096M');
require_library('connectors/MarineGEOAPI');
$timestart = time_elapsed();

$params['jenkins_or_cron']  = @$argv[1];
$params['filename']         = @$argv[2];
$params['form_url']         = @$argv[3];
$params['uuid']             = @$argv[4];

// print_r($params); exit;
/*Array(
    [jenkins_or_cron] => jenkins
    [filename] => 1574915471.zip
)*/
if($val = $params['filename']) $filename = $val;
else                           $filename = '';
if($val = $params['form_url']) $form_url = $val;
else                           $form_url = '';
if($val = $params['uuid'])     $uuid = $val;
else                           $uuid = '';
$resource_id = 'Dep-01'; //no longer used from here
$func = new MarineGEOAPI('');
$func->start($filename, $form_url, $uuid);
// Functions::get_time_elapsed($timestart);
?>