<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1816 
execution time: Took 5 hr 11 min (first time, un-cached)

xeno_canto	Tuesday 2019-09-17 05:12:49 PM	{"agent.tab":3482,"media_resource.tab":116748,"taxon.tab":10939,"vernacular_name.tab":10939}
xeno_canto	Wednesday 2019-09-18 01:45:48 AM{"agent.tab":3482,"media_resource.tab":116746,"taxon.tab":10939,"vernacular_name.tab":10939} //lost 2 due to non-standard filenames of mp3 files.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();
$resource_id = 'xeno_canto';
require_library('connectors/XenoCantoAPI');
$func = new XenoCantoAPI($resource_id);
$func->start();
unset($func);
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>