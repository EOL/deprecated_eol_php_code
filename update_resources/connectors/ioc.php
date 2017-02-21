<?php
namespace php_active_record;
/* connector for IOC Birdlist https://eol-jira.bibalex.org/browse/TRAM-499
estimated execution time:

taxon (with syn):   
media_resource:     
vernaculars:        
agents:             
references:         
occurrence          
measurements        
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IOCBirdlistAPI');
$timestart = time_elapsed();
$resource_id = 173;
$fishbase = new IOCBirdlistAPI(false, $resource_id);
$fishbase->get_all_taxa($resource_id);
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>