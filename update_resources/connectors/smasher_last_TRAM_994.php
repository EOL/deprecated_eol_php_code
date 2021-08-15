<?php
namespace php_active_record;
/* last smasher run TRAM-994 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/SmasherLastAPI_TRAM_994');
$timestart = time_elapsed();
$func = new SmasherLastAPI_TRAM_994(false);

/* START TRAM-994 */
$func->Transformations_for_all_taxa();                   echo("\n---- end Transformations_for_all_taxa ----\n");
/*
source:  2378792 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_6.tsv
destination:  2376204 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_993/final_taxonomy_7.tsv
was_container: [2588]
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>