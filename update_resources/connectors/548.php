<?php
namespace php_active_record;
/* 
estimated execution time: 1.45 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SouthAfricanVertebratesAPI');
$timestart = time_elapsed();
$resource_id = 548;
$func = new SouthAfricanVertebratesAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false);

// $resource_id = "548_orig";

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) {
    echo "\nThere is undefined parent(s):\n";
    print_r($undefined);
}
else echo "\nAll parents have entries.\n";
// */

// /* this will delete the working dir
$dir = CONTENT_RESOURCE_LOCAL_PATH."/".$resource_id;
if(is_dir($dir)) recursive_rmdir($dir);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>