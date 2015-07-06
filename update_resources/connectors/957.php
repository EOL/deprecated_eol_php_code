<?php
namespace php_active_record;
/* DATA-1618 internationalize wikipedia! Test case: German Wikipedia
					2015Jun2
media_resource.tab 	54605
taxon.tab 			27300
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikipediaRegionalAPI');
$timestart = time_elapsed();
$resource_id = 957;
$func = new WikipediaRegionalAPI($resource_id, 'de');
$func->generate_archive();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
