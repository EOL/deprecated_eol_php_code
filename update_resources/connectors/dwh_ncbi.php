<?php
namespace php_active_record;
/* MycoBank Classification - new spreadsheet download (TRAM-788)
estimated execution time: 
http://www.eol.org/content_partners/614/resources/671
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWH_NCBI_API');
// ini_set('memory_limit','5096M');
$timestart = time_elapsed();
$resource_id = "dwca_ncbi";
$func = new DWH_NCBI_API($resource_id);

$GLOBALS['ENV_DEBUG'] = true;

$func->start();
Functions::finalize_dwca_resource($resource_id);

/* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) {
    echo "\nThere is undefined parent(s): ".count($undefined)."\n";
    // print_r($undefined);
}
else echo "\nAll parents have entries.\n";
*/

/* this will delete the working dir
$dir = CONTENT_RESOURCE_LOCAL_PATH."/".$resource_id;
if(is_dir($dir)) recursive_rmdir($dir);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>