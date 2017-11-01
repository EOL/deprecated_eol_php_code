<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
set_time_limit(0);
ini_set("memory_limit","4000M"); // worked for the dwh_taxa.txt (https://eol-jira.bibalex.org/browse/TRAM-575)


// print_r($argv);
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //
$cmdline_params['resource_id']      = @$argv[2]; //resource_id or file_path
$cmdline_params['file_path']        = @$argv[3]; //one or the other, file_path or resource_id

print_r($cmdline_params);


// /* normal operation
$file = "sample/taxon.tsv";
$file = "sample/dwh_taxa.txt"; //original dwh_taxa.txt from the zip file was added with first row headers (https://eol-jira.bibalex.org/browse/TRAM-575)

require_library('connectors/DwCA_Utility_cmd');
$func = new DwCA_Utility_cmd();
echo "\ninput file to lib: [$file]\n";
if($info = $func->tool_generate_higherClassification($file)) {}
else echo "The file is not ready for processing. The file needs the minimum three fields column header: '<i>taxonID</i>', '<i>scientificName</i>' and '<i>parentNameUsageID</i>'";
// */



/* utility
$new_file = "temp/taxon.tsv"; //OR this should be the output from above
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries(pathinfo($new_file, PATHINFO_FILENAME), true, $new_file); //true means output will write to text file
echo "\nundefined parents: ".count($undefined_parents)."\n";
// OUTPUT IS: taxon_undefined_parent_ids.txt
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\nelapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\nelapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\nDone processing.\n\n";

?>
