<?php
namespace php_active_record;
/* 
This will use the image_sizes table and the dataObjects API to generate a DwCA file.
https://eol-jira.bibalex.org/browse/DATA-1740
estimated execution time:
*/

/* this didn't quite get the desired result. Used the EOLv2MetadataAPI.php, DATA-1718.php instead. */
exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CollectionsScrapeAPI');
$timestart = time_elapsed();

$resource_id = "image_sizes";
$collection_id = false;
$func = new CollectionsScrapeAPI($resource_id, $collection_id);
$func->start_image_sizes();
Functions::finalize_dwca_resource($resource_id, false, false); //3rd param true means resource folder will be deleted

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
