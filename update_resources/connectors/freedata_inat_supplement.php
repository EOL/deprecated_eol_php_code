<?php
namespace php_active_record;
/* supplementary iNat records: https://eol-jira.bibalex.org/browse/DATA-1700
1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

eol_inat_app in iNaturalist.org
https://www.inaturalist.org/oauth/applications/187

a good source of API reference
https://www.inaturalist.org/pages/api+reference

Change in algorithm:

1. no more every 2 month resets nor monthly resets. Daily harvest added/updated will continue.
2. after daily harvest, we remove all that is 2 months old date_added and date_updated.

---------------------------------------------------------------------
Result of initial one-time harvest:
total rows observations before removing old records: [195337]
total rows observations after removing old records: [193056]
---------------------------------------------------------------------
Result of first daily harvest:
total rows daily: [7559]
total rows observations before removing old records: [193109]
total rows observations after removing old records: [193108]
---------------------------------------------------------------------
total rows observations before removing old records: [269204]
total rows observations after removing old records: [266983]
total rows observations after removing old records: [270268] 9-11 ?
total rows observations after removing old records: [272798] 9-12
total rows observations after removing old records: [273101] 9-13 from eol-archive already
total rows observations after removing old records: [257847] 9-15 first daily using Jenkins
total rows observations after removing old records: [263650] 9-18
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
