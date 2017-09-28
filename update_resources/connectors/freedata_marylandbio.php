<?php
namespace php_active_record;
/* Connector for Maryland Biodiversity invasives service: https://eol-jira.bibalex.org/browse/DATA-1691

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$timestart = time_elapsed();

// $csv_url = "http://localhost/cp/FreshData/Maryland Biodiversity invasives/file.csv";                    //local copy
$csv_url = "https://www.marylandbiodiversity.com/services/exportInvasiveSpecies.php?dlkey=vanquish";    //actual remote source


$func = new FreeDataAPI("MarylandBio");
$func->generate_MarylandBio_archive($csv_url);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
