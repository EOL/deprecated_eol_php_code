<?php
namespace php_active_record;
/* connector for IOC Birdlist https://eol-jira.bibalex.org/browse/TRAM-499
estimated execution time:
                Feb2017 Apr2017 1-Nov2017   23Nov
taxon:          33750   33750   33507       33505
vernaculars:    11068   11068   11092       11090
occurrence      31172           42992
measurements    31172   43214   42992       42992

ioc-birdlist	Sunday 2018-05-20 12:51:02 PM	{"measurement_or_fact.tab":42989,"occurrence.tab":42989,"taxon.tab":33520,"vernacular_name.tab":11104}
ioc-birdlist	Sunday 2018-05-20 12:52:41 PM	{"measurement_or_fact.tab":42989,"occurrence.tab":42989,"taxon.tab":33520,"vernacular_name.tab":11104}
ioc	Sunday 2018-05-20 01:19:14 PM	{"measurement_or_fact.tab":42989,"occurrence.tab":42989,"taxon.tab":33520,"vernacular_name.tab":11104}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IOCBirdlistAPI');
$timestart = time_elapsed();
$resource_id = "ioc-birdlist";
$fishbase = new IOCBirdlistAPI(false, $resource_id);
$fishbase->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id);

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

$dir = CONTENT_RESOURCE_LOCAL_PATH."/".$resource_id;
if(is_dir($dir)) recursive_rmdir($dir);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>