<?php
namespace php_active_record;
/* a utility that can access parts of a DwCA
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DwCA_Utility');
$timestart = time_elapsed();
ini_set("memory_limit","4000M"); // trying for the dwh_try3.zip, didn't work yet
$GLOBALS['ENV_DEBUG'] = true;
//===========================================================================================new - start -- handles cmdline params

// /* //utility - useful when generating higherClassification
$dwca_file = "http://localhost/eol_php_code/applications/content_server/resources_3/polytraits_new.tar.gz";
$resource_id = "nothing here";
$func = new DwCA_Utility($resource_id, $dwca_file);
$download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1); //60*60*24*1
$func->count_records_in_dwca($download_options);
unset($func);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>