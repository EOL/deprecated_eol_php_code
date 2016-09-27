<?php
namespace php_active_record;
/* connector for Illinois Wildflowers
estimated execution time: 24 minutes
Connector scrapes the site: http://www.illinoiswildflowers.info/flower_insects/index.htm
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IllinoisWildflowersAPI');
$timestart = time_elapsed();

$resource_id = 34;
$func = new IllinoisWildflowersAPI();
$func->get_all_taxa($resource_id);

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 1000)
{
    Functions::set_resource_status_to_harvest_requested($resource_id);
    $command_line = "gzip -c " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml >" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml.gz";
    $output = shell_exec($command_line);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>