<?php
namespace php_active_record;
/* PESI
estimated execution time: 3-5 days
 - 1 day - generation of taxa list text file (taxa.txt)
 - taxa.txt is used to generate processed.txt

            8Mar2017
taxon       300701
with syn    

*/
// return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PesiAPI');
$timestart = time_elapsed();
$resource_id = "665";

// /* main operation
$func = new PesiAPI($resource_id);
// $func->get_all_taxa();   //main func to use
$func->get_all_taxa_v2("http://localhost/eol_php_code/tmp/pesi_txts_2017_03_07/processed.txt");   //used if taxa.txt and processed.txt are already generated elsewhere
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>