<?php
namespace php_active_record;
/* estimated execution time: 17 minutes 

168	Saturday 2018-04-14 11:18:48 AM	{"agent.tab":98,"media_resource.tab":129821,"taxon.tab":22302} - MacMini
168	Saturday 2018-04-14 11:37:37 AM	{"agent.tab":98,"media_resource.tab":129821,"taxon.tab":22302} - eol-archive

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BioImagesAPI');

$timestart = time_elapsed();
$resource_id = 168;
$func = new BioImagesAPI($resource_id);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id, false, true); //2nd param False - not a big file | 3rd param True - can delete working folder

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>
