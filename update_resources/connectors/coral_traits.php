<?php
namespace php_active_record;
/* DATA-1793 
coraltraits	Tuesday 2019-02-05 11:37:23 AM	{"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547} -- first run
coraltraits	Tuesday 2019-02-19 10:57:19 PM	{"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	    Wednesday 2019-02-20 12:23:55 AM{"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
cotr	Monday 2019-07-08 10:58:43 AM	    {"measurement_or_fact_specific.tab":56713,"occurrence_specific.tab":33518,"reference.tab":555,"taxon.tab":1547}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CoralTraitsAPI');
$timestart = time_elapsed();
$resource_id = "cotr"; //coraltraits
$func = new CoralTraitsAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
