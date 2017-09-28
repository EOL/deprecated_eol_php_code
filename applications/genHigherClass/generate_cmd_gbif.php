<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
set_time_limit(0);
ini_set("memory_limit","5000M"); //orig
ini_set("memory_limit","8000M"); //orig

/*
Being used by GBIF backbone, but can be used by any DwCA taxon extension. See below how to use:
*/

/* Important settings
Apache httpd.conf:
    Timeout 1200
    
php.ini:
    upload_max_filesize = 10M
    post_max_size = 10M
*/

/* normal operation
$file = "sample/GBIF_Taxon.tsv";
// $file = "sample/taxon.tsv";

require_library('connectors/DwCA_Utility_cmd');
$func = new DwCA_Utility_cmd();

echo "\ninput file to lib: [$file]\n";
if($info = $func->tool_generate_higherClassification($file)) {}
else echo "The file is not ready for processing. The file needs the minimum three fields column header: '<i>taxonID</i>', '<i>scientificName</i>' and '<i>parentNameUsageID</i>'";
*/

/* additional task: pruning GBIF backbone: https://eol-jira.bibalex.org/browse/TRAM-552
require_library('connectors/GBIFtaxaAPI');
$func = new GBIFtaxaAPI();
$new_file = $func->prune_gbif_backbone_taxa("temp/GBIF_Taxa_accepted.tsv"); // pruning alone takes 151 hours (6.3 days)
*/

/* utility - this takes 75 minutes
$new_file = "temp/GBIF_Taxa_accepted_pruned.tsv"; //OR this should be the output from above
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries(pathinfo($new_file, PATHINFO_FILENAME), true, $new_file); //true means output will write to text file
echo "\nundefined parents: ".count($undefined_parents)."\n";
// OUTPUT IS: GBIF_Taxa_accepted_pruned_undefined_parent_ids.txt
*/

/* utility to cache AND/OR generate "GBIF_invalid_descendants.txt"
// INPUT IS: GBIF_Taxa_accepted_pruned_undefined_parent_ids.txt
require_library('connectors/GBIFtaxaAPI');
$func = new GBIFtaxaAPI();
$new_file = $func->get_GBIF_invalid_descendants();
// OUTPUT IS: GBIF_invalid_descendants.txt
*/

/* additional task: pruning FURTHER: including descendants of synonyms and doubtful parents | this alone takes: 8.4 hours
require_library('connectors/GBIFtaxaAPI');
$func = new GBIFtaxaAPI();
$new_file = $func->prune_gbif_backbone_taxa_FURTHER("temp/GBIF_Taxa_accepted_pruned.tsv"); // pruning takes ???
echo "\nOUTPUT IS: $new_file\n";
*/

// /* utility - this takes 1.1 hours, but 0 undefined parents | if this alone only it takes 64.56 minutes
$new_file = "temp/GBIF_Taxa_accepted_pruned_final.tsv"; //OR this should be the output from above
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries(pathinfo($new_file, PATHINFO_FILENAME), true, $new_file); //true means output will write to text file
echo "\nundefined parents: ".count($undefined_parents)."\n";
// OUTPUT IS: GBIF_Taxa_accepted_pruned_final_undefined_parent_ids.txt
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\nelapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\nelapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\nDone processing.\n\n";

function get_ext_of_orig_file_in_zip($orig)
{
    $temp = pathinfo($orig, PATHINFO_FILENAME);
    return pathinfo($temp, PATHINFO_EXTENSION);
}
?>
