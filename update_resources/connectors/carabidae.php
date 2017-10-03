<?php
namespace php_active_record;
/* Connector scrapes the classification from the site: http://carabidae.org [COLLAB-1002]
execution time: 
taxon.tab
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CarabidaeAPI');
$timestart = time_elapsed();

$resource_id = "carabidae";
$func = new CarabidaeAPI($resource_id);
$func->convert_to_dwca();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>