<?php
namespace php_active_record;
/* estimated execution time: 6 minutes
                                2014    2015    2015
                      old       Oct2    Jan28    Jun29
reference:          : 1         1       1        1
Text                : 26220     26205   26205    26205
#Distribution       : 26187     26205   26205    26205
#ConservationStatus : 33                none    none
taxon:              : 33590     33641   33641    33641
measurementorfact               31339   31339    31339

ICTV-virus_taxonomy	                            Thursday 2018-06-07 01:04:22 AM	{"taxon.tab":5844}
ICTV-virus_taxonomy-with-higherClassification	Thursday 2018-06-07 01:04:33 AM	{"taxon.tab":5844}

ICTV-virus_taxonomy	Tuesday 2019-03-26 11:58:22 AM	{"taxon.tab":5844} - MacMini

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ICTVdwcaAPI');

$timestart = time_elapsed();
$resource_id = "ICTV-virus_taxonomy";
$func = new ICTVdwcaAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);
// */

/* Note: after which we run dwca_utility.php to get higherClassification column */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
