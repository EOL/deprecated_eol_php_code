<?php
namespace php_active_record;
/* This is a generic connector for converting CSV DwCA to EOL DwCA. First client for this connector is the myspecies.info Scratchpad resources.
e.g. http://www.eol.org/content_partners/373/resources/268 -- Bryozoa of the British Isles

Note: The first choice to use is: php update_resources/connectors/dwca_utility.php _ {resource_id}
But it is running out of memory because the text files are actually CSV files. And dwca_utility.php loads entire extension into memory.

resource 430 used its own 430.php

template:
$resources[res_id] = array('dwca' => "http_eol_dwca_zip", 'bigfileYN' => false); //res_name
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CSV2DwCA_Utility_generic');
$timestart = time_elapsed();


$resources[268] = array('dwca' => "http://britishbryozoans.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Bryozoa of the British Isles
$resources[220] = array('dwca' => "http://diptera.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Scratchpad export - Diptera taxon pages
$resources[549] = array('dwca' => "http://antkey.org/eol-dwca.zip", 'bigfileYN' => false); //Antkey
$resources[363] = array('dwca' => "http://pngbirds.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //PNG_Birds
$resources[754] = array('dwca' => "http://anolislizards.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Anolis Scratchpad
$resources[755] = array('dwca' => "http://xyleborini.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Xyleborini Ambrosia Beetles
$resources[756] = array('dwca' => "http://neotropical-pollination.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Neotropical Pollination
$resources[884] = array('dwca' => "http://phthiraptera.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); //Phthiraptera

foreach($resources as $resource_id => $info) {
    $func = new CSV2DwCA_Utility_generic($resource_id, $info['dwca']);
    $func->convert_archive();
    Functions::finalize_dwca_resource($resource_id, $info['bigfileYN'], true); //3rd param is deleteFolderYN ------- 2nd params is true coz it is a big file
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
