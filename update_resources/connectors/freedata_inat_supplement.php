<?php
namespace php_active_record;
/* supplementary iNat records: https://eol-jira.bibalex.org/browse/DATA-1700
1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

eol_inat_app in iNaturalist.org
https://www.inaturalist.org/oauth/applications/187

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreshDataInatSupplementAPI');
$timestart = time_elapsed();

$func = new FreshDataInatSupplementAPI("inat_supplement"); //'inat_supplement' will be a folder name
$func->start();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
