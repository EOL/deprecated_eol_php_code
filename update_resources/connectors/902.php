<?php
namespace php_active_record;
/* estimated execution time:  minutes

http://rs.tdwg.org/dwc/terms/taxon:     959
http://eol.org/schema/agent/agent:      6
http://purl.org/dc/dcmitype/StillImage: 2244
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/STRInewAPI');

$timestart = time_elapsed();
$resource_id = 902;
$func = new STRInewAPI($resource_id);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\nDone processing.";
?>
