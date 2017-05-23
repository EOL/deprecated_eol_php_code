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

// /* additional task: pruning GBIF backbone: https://eol-jira.bibalex.org/browse/TRAM-552
require_library('connectors/GBIFtaxaAPI');
$func = new GBIFtaxaAPI();
$func->prune_gbif_backbone_taxa();
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "elapsed time = $elapsed_time_sec seconds";
echo "\nelapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\nDone processing.\n\n";

function get_ext_of_orig_file_in_zip($orig)
{
    $temp = pathinfo($orig, PATHINFO_FILENAME);
    return pathinfo($temp, PATHINFO_EXTENSION);
}
?>