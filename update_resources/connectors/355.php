<?php
namespace php_active_record;
/* connector for Avibase - IOC World Bird Names
estimated execution time: 3 minutes (9 hours with synonyms)
Connector scrapes the Avibase site.

                    2015-05-10	05-28	6-27
taxon.tab           27389		27400	19542
vernacular_name.tab 747213		747748	747745

breakdown	 05-28	6-27
[]        => 7931	 7931
[synonym] => 19469	11611
total	  =>		19542


*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AvibaseAPIv2');

$timestart = time_elapsed();
$resource_id = 355;

$func = new AvibaseAPIv2($resource_id, 'ioc');
$func->generate_archive($resource_id);
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>
