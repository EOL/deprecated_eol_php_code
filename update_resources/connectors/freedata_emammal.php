<?php
namespace php_active_record;
/* e-mammal for FreshData: https://eol-jira.bibalex.org/browse/DATA-1659 

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$timestart = time_elapsed();

/* local - during development
$local_path = "/opt/homebrew/var/www/cp/FreshData/eMammal";
*/

// /* remote - actual
$local_path = "/extra/other_files/FreshData/eMammal";
// */

$func = new FreeDataAPI("eMammal");
$func->generate_eMammal_archive($local_path);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
