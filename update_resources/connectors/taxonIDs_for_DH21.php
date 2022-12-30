<?php
namespace php_active_record;
/* TRAM-995: taxonIDs for DH2.1
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v21_TRAM_995');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed(); exit("\nA. Moved to ver. 2.\n");

// $DH1_id = 173;
// $DH2_id = 300;
// $both_DH1[$DH1_id][$DH2_id][] = 'rank test OK';
// $DH2_id = 301;
// $both_DH1[$DH1_id][$DH2_id][] = 'ancestry test OK';
// $DH2_id = 300;
// $both_DH1[$DH1_id][$DH2_id][] = 'ancestry test OK';
// print_r($both_DH1);

/*Array(
    [173] => Array(
            [300] => Array(
                    [0] => rank test OK
                    [1] => ancestry test OK
                )
            [301] => Array(
                    [0] => ancestry test OK
                )
        )
)
----------------------
[173] [300]
Array(
    [0] => rank test OK
    [1] => ancestry test OK
)

[173] [301]
Array(
    [0] => ancestry test OK
)

Array(
    [173] => 300
)
*/

// foreach($both_DH1 as $dh1_id => $rekord) {
//     foreach($rekord as $dh2_id => $tests) {
//         echo "\n[$dh1_id] [$dh2_id]\n"; print_r($tests);
//         if(count($tests) > 1) $DH1_passed_tests[$dh1_id] = $dh2_id;
//     }
// }
// print_r($DH1_passed_tests);
/*Array(
    [173] => 300
)*/
// echo "\nDH1_passed_tests: ".count($DH1_passed_tests)."\n";
// exit;

/* test
$pr_id = 24;
$pr_id = sprintf("%011d", $pr_id);
exit("\n$pr_id\n");
*/
//############################################################ start main
$resource_id = "DH_v21";
$func = new DH_v21_TRAM_995($resource_id);

/* copied template
$func->save_all_ids_from_all_hierarchies_2MySQL(); //used source hierarchies. Manually done alone. Generates write2mysql_v2.txt. Table ids_scinames is needed below by generate_dwca().

$ mysql -u root -p --local-infile DWH;
copy table structure only:
mysql> CREATE TABLE ids_scinames LIKE ids_scinames_v1;
to load from txt file:
mysql> load data local infile '/Volumes/AKiTiO4/d_w_h/2019_04/zFiles/write2mysql_v2.txt' into table ids_scinames;

To make a backup of minted_records table
mysql> CREATE TABLE minted_records_bak LIKE minted_records;
mysql> INSERT minted_records_bak SELECT * FROM minted_records;
*/

// /* main operation
$func->start($resource_id);
unset($func);
// */

$func->generate_dwca(); //works OK - final step
Functions::finalize_dwca_resource($resource_id, true, false);
run_diagnostics($resource_id);

/* stats:
counting: [/opt/homebrew/var/www/eol_php_code/applications/content_server/resources/DH_v1_1_postproc/taxon.tab] total: [2338864]
*/


//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
Function run_diagnostics($resource_id) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
?>