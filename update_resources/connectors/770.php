<?php
namespace php_active_record;
/* americaninsects.net - length: structured data
estimated execution time:

770	Thursday 2019-07-11 06:58:32 AM	{"measurement_or_fact_specific.tab":977,"occurrence_specific.tab":976,"reference.tab":1,"taxon.tab":919}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AmericanInsectsAPI');
$timestart = time_elapsed();
$resource_id = 770;
$func = new AmericanInsectsAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>