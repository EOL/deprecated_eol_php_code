<?php
namespace php_active_record;
/* ALL THIS FROM COPIED TEMPLATE: marine_geo_image.php
Instructions here: https://eol-jira.bibalex.org/browse/COLLAB-1004?focusedCommentId=64188&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64188
*/
/* how to run during dev:
1st test case by Katja from ticket:
    php update_resources/connectors/branch_graft.php _ '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.001884" , "newfile_File_A":"File_A_1688396971.tab" , "newfile_File_B":"File_B_1688396971.tsv" , "fileA_taxonID":"EOL-000000095511" , "fileB_taxonID":"EOL-000000095511" , "uuid":"1688396971" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"amoebozoatest.tsv" }'
where File_A_1688396971.tab is in /eol_php_code/applications/taxonomic_validation/temp/
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;  //set to false in production
/* during dev only
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
*/
ini_set('memory_limit','14096M');
require_library('connectors/BranchGraftRules');
require_library('connectors/BranchGraftAPI');
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
$params['json']             = @$argv[2];

if($GLOBALS['ENV_DEBUG']) { 
    // echo "<pre>"; 
    // print_r($params); //good debug
    // echo "</pre>"; 
}
/* Array(
    [jenkins_or_cron] => _
    [json] => {"Filename_ID":"","Short_Desc":"" , "timestart":"0.002263" , "newfile_File_A":"File_A_1688396971.tab" , "newfile_File_B":"File_B_1688396971.tsv" , "fileA_taxonID":"EOL-000000095511" , "fileB_taxonID":"eli02" , "uuid":"1688396971" }
)*/

if($val = $params['json'])     $json = $val;
else                           $json = '';

/* Array(
    [Filename_ID] => 
    [Short_Desc] => 
    [timestart] => 0.002263
    [newfile_File_A] => File_A_1688396971.tab
    [newfile_File_B] => File_B_1688396971.tsv
    [fileA_taxonID] => eli01
    [fileB_taxonID] => eli02
    [uuid] => 1688396971
)*/
$arr = json_decode($json, true); //print_r($arr); exit("\n-stop muna 1-\n");
$func = new BranchGraftAPI('branch_graft');
// echo "\n[$timestart]\n"; exit; //[0.035333]
$func->start($arr['uuid'], $json); // normal operation
// $func->prepare_download_link(); //test only
// Functions::get_time_elapsed($arr['timestart']); //working but not resembling the real run time
?>