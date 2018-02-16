<?php
namespace php_active_record;
/* connector for STRI maps (Shorefishes of the tropical eastern Pacific online information system)
estimated execution time: 16 minutes
Connector processes the original XML resource (http://eol.org/content_partners/44/resources/35) 
and checks if the map image exists. If it does, it generates a map dataObject for the Maps tab.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

echo "\nUsed lifedesk_combine.php instead.\n";
return;

require_library('connectors/STRImapsAPI');
$timestart = time_elapsed();
$resource_id = 358;
$avibase = new STRImapsAPI();
$avibase->get_all_taxa($resource_id);
Functions::set_resource_status_to_harvest_requested($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>