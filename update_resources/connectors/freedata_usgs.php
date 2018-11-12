<?php
namespace php_active_record;
/* e-mammal for FreshData: https://eol-jira.bibalex.org/browse/DATA-1683 

1. Resource is then added to: https://github.com/gimmefreshdata
2. then added to queue in: http://archive.effechecka.org/

                                2017-Jun-06
observations.txt rows total:    463616

Todo: get remote raw file using script
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FreeDataAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

echo "\nPartner service is down.\n"; return;


$csv_url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/FreshData/USGS/SpeciesList.csv";
// $csv_url = "http://localhost/cp_new/FreshData/USGS/SpeciesList.csv"; //use [csv] button below this page: https://nas.er.usgs.gov/queries/SpeciesList.aspx

$func = new FreeDataAPI("usgs-nas"); //usgs_nonindigenous_aquatic_species
$func->generate_usgs_archive($csv_url);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
