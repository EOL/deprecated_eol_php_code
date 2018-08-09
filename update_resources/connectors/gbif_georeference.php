<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI');
$timestart = time_elapsed();
$resource_id = 1;

/* tests
$path2 = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/GBIF_taxa_csv/";
$taxonkey = 2;

$final = get_md5_path($path2, $taxonkey);
echo "\n[$final]\n";
exit;
*/


$func = new GBIFoccurrenceAPI($resource_id);

$func->start(); //normal operation

// $func->save_ids_to_text_from_many_folders(); //utility

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function get_md5_path($path, $taxonkey)
{
    $md5 = md5($taxonkey);
    $cache1 = substr($md5, 0, 2);
    $cache2 = substr($md5, 2, 2);
    if(!file_exists($path . $cache1)) mkdir($path . $cache1);
    if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
    return $path . "$cache1/$cache2/";
}
?>
