<?php
namespace php_active_record;
/* execution time: 2.7 hours 

http://www.eol.org/content_partners/557/resources

505	Sunday 2018-04-15 03:05:41 AM	{"media_resource.tab":26654,"reference.tab":78635,"taxon.tab":1015} - MacMini
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/USDAfsfeisAPI');
$timestart = time_elapsed();

$resource_id = "505";
$group["Plantae"] = "plants";

$func = new USDAfsfeisAPI($resource_id, $group);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = $elapsed_time_sec seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.";
?>
