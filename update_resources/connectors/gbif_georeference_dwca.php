<?php
namespace php_active_record;
/* DATA-1748: GBIF map data harvest
This will generate the map data (.json files) for the EOL maps.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI_DwCA');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true;

/* source: https://stackoverflow.com/questions/3137094/how-to-count-lines-in-a-document
$output = shell_exec("wc -l < /Volumes/AKiTiO4/eol_pub_tmp/google_maps/occurrence_downloads/DwCA/Animalia/multimedia.txt");
$output = trim($output);
echo "\n[$output]\n";
$output++;
echo "\n[$output]\n";
exit("\n");
*/

$func = new GBIFoccurrenceAPI_DwCA();
$func->start(); //normal operation

// $func->save_ids_to_text_from_many_folders(); //utility, important as last step. This is now added to main program $func->start(); 

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

?>
