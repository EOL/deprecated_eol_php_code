<?php
namespace php_active_record;
/* last smasher run */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/SmasherLastAPI');
$timestart = time_elapsed();
$func = new SmasherLastAPI();

/* ran already OK
$func->sheet1_Move_DH2_taxa_to_new_parent();    echo "\n-end sheet1_Move_DH2_taxa_to_new_parent-\n";
$func->sheet2_Merge_DH2_taxa();                 echo "\n-end sheet2_Merge_DH2_taxa-\n";
$func->sheet3_Split_DH2_taxa();                 echo "\n-end sheet3_Split_DH2_taxa-\n";
$func->sheet4_Delete_DH2_taxa();                echo "\n-end sheet4_Delete_DH2_taxa-\n";
*/
/* ran already OK
$func->July7_num_1_2(); echo("\n-end Jul7_num1-\n");
$func->July7_num_2_delete(); echo("\n-end July7_num_2_delete-\n");
*/
/*
source      :  2379530 /Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_5.tsv
destination :  2378798 /Volumes/AKiTiO4/d_w_h/last_smasher/test/final_taxonomy_6.tsv
*/
/* START TRAM-993 */
$func->A_Clean_up_deadend_branches(); echo("\n---- end A_Clean_up_deadend_branches ----\n");


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>