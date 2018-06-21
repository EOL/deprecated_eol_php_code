<?php
namespace php_active_record;
/* estimated execution time:
487	Thursday 2018-06-21 02:07:15 AM	{"media_resource.tab":257,"reference.tab":69,"taxon.tab":162,"vernacular_name.tab":119} -> eol-archive
*Un-published resource
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SciELOAPI');

$timestart = time_elapsed();
$resource_id = "487";

$scielo_connector = new SciELOAPI($resource_id);
$scielo_connector->get_all_taxa();

Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
exit("\n Done processing.");
?>
