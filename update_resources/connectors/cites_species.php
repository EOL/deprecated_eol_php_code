<?php
namespace php_active_record;
/* DATA-1790: CITES species records
cites_taxa	Wednesday 2019-01-02 04:09:07 AM	{"measurement_or_fact.tab":18128,"occurrence.tab":18128,"taxon.tab":75623,"vernacular_name.tab":30384}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CITESspeciesAPI');
ini_set('memory_limit','7096M'); //required for checking uniqueness of measurementID
$timestart = time_elapsed();
$resource_id = 'cites_taxa';
$func = new CITESspeciesAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>