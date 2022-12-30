<?php
namespace php_active_record;
/* last smasher run TRAM-994 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['what']             = @$argv[2]; //useful here

require_library('connectors/SmasherLastAPI_TRAM_994');
$timestart = time_elapsed();
$func = new SmasherLastAPI_TRAM_994(false);

// $str = "pecten-veneris";
// $str = @$var[4];
// $str = "discoidea.ip";
// $str = str_replace(array("-","."), "", $str);
// if(ctype_lower($str)) echo "\nall small\n";
// else echo "\nnot all small\n";
// exit;

// $str = "Chaunaca9nthid 5 1.7";
// $str = "Halimeda taenicola.4";
// $str = "Polysiphonia sertularioides-3";
// $str = "Agonum ruficorne2";
// if(preg_match_all('/\d+/', $str, $a)) print_r($a[0]);
// exit("\n");

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
$arr = file('/opt/homebrew/var/www/eol_php_code/somewhat.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
print_r($arr);
$arr = array_map('trim', $arr); print_r($arr);
exit("\n-end test-\n");
*/

/* START TRAM-994 */
/*
$func->Transformations_for_all_taxa();                  echo("\n---- end Transformations_for_all_taxa ----\n");
source:       2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/DH_2_1_Jul26/taxon.tab
destination:  2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv
*/
// $func->generate_descendants_for_Viruses_Bacteria_Archaea(); //run once only
/*
$func->Transformations_for_species_in_Eukaryota();      echo("\n---- end Transformations_for_species_in_Eukaryota ----\n");
source:       2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv
destination:  2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_2.tsv
*/
/*
$func->Transformations_for_subgenera_in_Eukaryota();    echo("\n---- end Transformations_for_subgenera_in_Eukaryota ----\n");
source:       2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_2.tsv
destination:  2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_3.tsv
*/
/*
$func->Remove_taxa_with_malformed_canonicalName_values();    echo("\n---- end Remove_taxa_with_malformed_canonicalName_values ----\n");
// source:  2376320 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_3.tsv
// destination:  2376226 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_4.tsv
*/
/*
$func->Delete_descendants_of_taxa_from_report();    echo("\n---- end Delete_descendants_of_taxa_from_report ----\n");
// parent_ids: 94
// descendant_ids: 1
// source:  2376226 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_4.tsv
// destination:  2376225 /Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_5.tsv
*/

$func->investigate_descendants_of_removed_taxa(); //a utility

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function first_char_is_capital($str)
{
    $str = trim($str);
    if(ctype_upper($str[0])) return true;
}


?>