<?php
namespace php_active_record;
/* This is a generic connector for converting CSV DwCA to EOL DwCA.

Note: The first choice to use is: php update_resources/connectors/dwca_utility.php _ {resource_id}
But it is running out of memory because the text files are actually CSV files. And dwca_utility.php loads entire extension into memory.

resource 430 used its own 430.php
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CSV2DwCA_Utility_generic');
// ini_set('memory_limit','4096M');
$timestart = time_elapsed();

$resources[268] = array('dwca' => "http://britishbryozoans.myspecies.info/eol-dwca.zip", 'bigfileYN' => false); 


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
