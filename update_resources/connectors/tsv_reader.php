<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/COLLAB-1006 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/TSVReaderAPI');
$func = new TSVReaderAPI();
$path = "/Users/eliagbayani/Desktop/COLLAB-1006/z_from_Katja/";

// /*
$tsv_file = "3_1051_doi.org_10.1073_pnas.1907847116_remove.txt";
// $tsv_file = "3_1051_doi.org_10.1073_pnas.1907847116_fixedOnWikiData.txt";
$tsv_file = $path.$tsv_file;
$ids = $func->read_tsv($tsv_file, "array_of_pageIDs");
echo "\nids: ".count($ids)."\n";
// */

/*
$tsv_file = "3_1051_doi.org_10.1073_pnas.1907847116_IDcorrections.txt";
$tsv_file = $path.$tsv_file;
$func->read_tsv($tsv_file, "IDcorrections_syntax");
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>