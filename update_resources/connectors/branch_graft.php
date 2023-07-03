<?php
namespace php_active_record;
/* ALL THIS FROM COPIED TEMPLATE: marine_geo_image.php
Instructions here: https://eol-jira.bibalex.org/browse/COLLAB-1004?focusedCommentId=64188&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64188
*/
/* how to run during dev:
$json = '{"Proj":"KANB", "Dept":"FISH", "Lic":"CreativeCommons – Attribution Non-Commercial (by-nc)", "Lic_yr":"", "Lic_inst":"", "Lic_cont":""}';
php update_resources/connectors/marine_geo_image.php _ image_input.xlsx _ _ '$json'
php update_resources/connectors/marine_geo_image.php _ _ 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/image_input.xlsx' uuid001 '$json'

from jenkins_call.php: 1686049284
/opt/homebrew/opt/php@5.6/bin/php taxonomic_validation.php jenkins '1686049284.tab' _ _ '{"Filename_ID":"","Short_Desc":"test" , "timestart":"0.009732"}'
php update_resources/connectors/taxonomic_validation.php _ '1686049284.tab' _ _ '{"Filename_ID":"","Short_Desc":"test" , "timestart":"0.009732"}']
where 1686049284.tab is in /eol_php_code/applications/taxonomic_validation/temp/
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;  //set to false in production
/* during dev only
$GLOBALS['ENV_DEBUG'] = true;   //set to true when debugging
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
ini_set('error_reporting', E_ALL);
*/
ini_set('memory_limit','14096M');
require_library('connectors/TaxonomicValidationRules');
require_library('connectors/TaxonomicValidationAPI');
// $timestart = time_elapsed(); //use the one from jenkins_call.php

/* tests
// $path = "/opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/Taxonomic_Validation/1686044073.tar.gz";
// print_r(pathinfo($path));
// echo "\n[".pathinfo($path, PATHINFO_BASENAME)."]\n";
-------------------------------------
// $str = "abcdefg";
// echo("\n".substr($str,0,-3)."\n"); //remove ending strings
// echo("\n".substr($str, -3)."\n"); //capture/get ending strings
exit("\n-end tests-\n");
*/

$params['jenkins_or_cron']  = @$argv[1];
$params['filename']         = @$argv[2];
$params['form_url']         = @$argv[3];
$params['uuid']             = @$argv[4];
$params['json']             = @$argv[5];

if($GLOBALS['ENV_DEBUG']) print_r($params); //good debug
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
$func = new TaxonomicValidationAPI('taxonomic_validation');
// echo "\n[$timestart]\n"; exit; //[0.035333]
$func->start($filename, $form_url, $uuid, $json);
// $func->prepare_download_link(); //test only

$arr = json_decode($json, true); // print_r($arr);
// Functions::get_time_elapsed($arr['timestart']); //working but not resembling the real run time
?>