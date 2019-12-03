<?php
namespace php_active_record;
/*
Instructions here: https://eol-jira.bibalex.org/browse/COLLAB-1004?focusedCommentId=64188&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64188
*/
/* how to run:
$json = '{"Proj":"KANB", "Dept":"FISH", "Lic":"CreativeCommons – Attribution Non-Commercial (by-nc)", "Lic_yr":"", "Lic_inst":"", "Lic_cont":""}';
php update_resources/connectors/marine_geo_image.php _ image_input.xlsx _ _ '$json'
php update_resources/connectors/marine_geo_image.php _ _ 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/image_input.xlsx' uuid001 '$json'
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
$params['form_url']         = @$argv[3];
$params['uuid']             = @$argv[4];
$params['json']             = @$argv[5];

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
if($val = $params['json'])     $json = $val;
else                           $json = '';

$resource_id = ''; //no longer used from here
$func = new MarineGEOAPI('specimen_image_export');
$func->start_image_export($filename, $form_url, $uuid, $json);
// Functions::get_time_elapsed($timestart);
?>