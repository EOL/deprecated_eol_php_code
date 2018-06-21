<?php
namespace php_active_record;
/* Catalogue of SINA Maps
estimated execution time: 10 minutes

latest nos.:
    image/gif: 732
    image/jpeg: 6
    Total: 738
    taxon: 379
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SINAMapsAPI');

$timestart = time_elapsed();
$resource_id = 670;
$func = new SINAMapsAPI($resource_id);

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true); //2nd param false means not a big file; 3rd param true means can delete resource folder in resources directory.

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
