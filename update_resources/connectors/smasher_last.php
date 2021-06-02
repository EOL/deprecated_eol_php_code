<?php
namespace php_active_record;
/* last smasher run */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/SmasherLastAPI');
$timestart = time_elapsed();
$func = new SmasherLastAPI();

$func->sheet1_Move_DH2_taxa_to_new_parent();    echo "\n-end sheet1_Move_DH2_taxa_to_new_parent-\n";
$func->sheet2_Merge_DH2_taxa();                 echo "\n-end sheet2_Merge_DH2_taxa-\n";
$func->sheet3_Split_DH2_taxa();                 echo "\n-end sheet3_Split_DH2_taxa-\n";
$func->sheet4_Delete_DH2_taxa();                echo "\n-end sheet4_Delete_DH2_taxa-\n";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
