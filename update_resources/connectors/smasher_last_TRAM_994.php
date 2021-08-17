<?php
namespace php_active_record;
/* last smasher run TRAM-994 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/SmasherLastAPI_TRAM_994');
$timestart = time_elapsed();
$func = new SmasherLastAPI_TRAM_994(false);
/*
$var = "Coeloplana (Benthoplana) Fricke & Plante, 1971"; //Coeloplana (Benthoplana) Fricke & Plante, 1971 -> Coeloplana subgen. Benthoplana
$arr = explode(" ", $var);
$second = $arr[1];
if($second[0] == "(" && substr($second, -1) == ")") {
    $second = str_replace("(", "", $second);
    $second = str_replace(")", "", $second);
    $new = $arr[0]. " subgen. " . $second;
    echo "\n[$new]\n";
}
exit("\n-end test-\n");
*/
/*
$arr = file('/Library/WebServer/Documents/eol_php_code/somewhat.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
print_r($arr);
$arr = array_map('trim', $arr); print_r($arr);
exit("\n-end test-\n");
*/

/* START TRAM-994 */
// $func->Transformations_for_all_taxa();                  echo("\n---- end Transformations_for_all_taxa ----\n");
// source:          2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/DH_2_1_Jul26/taxon.tab
// destination:     2376321 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv

// $func->Transformations_for_species_in_Eukaryota();      echo("\n---- end Transformations_for_species_in_Eukaryota ----\n");

$func->Transformations_for_subgenera_in_Eukaryota();    echo("\n---- end Transformations_for_subgenera_in_Eukaryota ----\n");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>