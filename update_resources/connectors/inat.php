<?php
namespace php_active_record;
/*connector for iNaturalist: DATA-1594 reverse connector, EOL data back to iNat.
This doesn't create a resource for ingestion but rather a text file of URLs to be given back to the partner
estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/INaturalistAPI');
$collection_id = 36789; //for iNat
$func = new INaturalistAPI($collection_id);
$func->generate_link_backs();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\nelapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>