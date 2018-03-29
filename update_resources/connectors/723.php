<?php
namespace php_active_record;
/*
NCBI, GGBN, GBIF, BHL, BOLDS database coverages
estimated execution time: ~3 days

723	Wednesday 2018-03-28 10:17:55 PM	{"measurement_or_fact.tab":116434,"occurrence.tab":116434,"taxon.tab":9913} - MacMini
723	Wednesday 2018-03-28 10:54:54 PM	{"measurement_or_fact.tab":116434,"occurrence.tab":116434,"taxon.tab":9913}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIGGIqueryAPI');
$timestart = time_elapsed();
$resource_id = 723;
$func = new NCBIGGIqueryAPI($resource_id);

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);

/* not yet implemented
sleep(60);
$func->generate_spreadsheet($resource_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function count_subfamily_per_database() // call this function above to run the report
{
    $databases = array("bhl", "ncbi", "gbif", "bolds"); // nothing for ggbn
    foreach($databases as $database) {
        $func->count_subfamily_per_database(DOC_ROOT . "/tmp/dir_" . $database . "/" . $database . ".txt", $database);
    }
}

?>