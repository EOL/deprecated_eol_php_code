<?php
namespace php_active_record;
/* Catalogue of Hymenoptera
estimated execution time: 10 minutes
http://www.eol.org/content_partners/611/resources/664

664	Monday 2018-10-22 12:08:53 PM	{"media_resource.tab":23491,"taxon.tab":21334}  MacMini
664	Monday 2018-10-22 12:10:09 PM	{"media_resource.tab":23491,"taxon.tab":21334}  eol-archive

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/HymenopteraAPI');

$timestart = time_elapsed();
$resource_id = 664;
$func = new HymenopteraAPI($resource_id);

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
