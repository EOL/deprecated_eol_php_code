<?php
namespace php_active_record;
/* DATA-1791 
ues	Thursday 2019-02-07 11:16:25 AM	{"measurement_or_fact_specific.tab":2351,"occurrence_specific.tab":2351,                     "taxon.tab":2282,"vernacular_name.tab":2086}
ues	Tuesday 2019-02-12 10:11:12 AM	{"measurement_or_fact_specific.tab":2351,"occurrence_specific.tab":2351,"reference.tab":3828,"taxon.tab":2282,"vernacular_name.tab":2086}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/USAendangeredSpeciesAPI');
$timestart = time_elapsed();
$resource_id = "ues"; //USA endangered species
$func = new USAendangeredSpeciesAPI($resource_id);

$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
