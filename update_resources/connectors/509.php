<?php
namespace php_active_record;
/* execution time: 21 minutes 
http://www.eol.org/content_partners/557/resources

509	Thursday 2018-08-02 11:16:03 AM	{"media_resource.tab":2559,"reference.tab":9003,"taxon.tab":128,"vernacular_name.tab":128}  eol-archive
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/USDAfsfeisAPI');
$timestart = time_elapsed();

$resource_id = "509";
$group["Animalia"] = "animals";

$func = new USDAfsfeisAPI($resource_id, $group);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = $elapsed_time_sec seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.";
?>
