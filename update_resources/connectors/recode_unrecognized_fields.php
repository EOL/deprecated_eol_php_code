<?php
namespace php_active_record;
/* DATA-1875: recoding unrecognized fields
php update_resources/connectors/recode_unrecognized_fields.php _ ioc-birdlist                 
php update_resources/connectors/recode_unrecognized_fields.php _ print_report_opendata        
php update_resources/connectors/recode_unrecognized_fields.php _ process_OpenData_resources   

php update_resources/connectors/recode_unrecognized_fields.php _ 201_meta_recoded
php update_resources/connectors/recode_unrecognized_fields.php _ copepod_sizes_Archive

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/RecodeUnrecognizedFieldsAPI');
$timestart = time_elapsed();
// ini_set("memory_limit","4000M");
// $GLOBALS['ENV_DEBUG'] = true;
// print_r($argv);
$cmdline_params['jenkins_or_cron']                  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']                      = @$argv[2]; //useful here; e.g. "ioc-birdlist". Assumed to be a DwCA "ioc-birdlist.tar.gz"
print_r($cmdline_params);

$resource_id = $cmdline_params['resource_id'];
/* not essential
if($resource_id = @$cmdline_params['resource_id']) {}
else exit("\nERROR: Missing param.\n");
*/

// /* //main operation
$func = new RecodeUnrecognizedFieldsAPI($resource_id);
// $func->scan_dwca(); //utility                                       --- working OK, but may need some adjustments
if($resource_id == 'print_report_opendata')          $func->print_report('opendata'); //default report
elseif($resource_id == 'print_report')               $func->print_report('local path');
elseif($resource_id == 'process_OpenData_resources') $func->process_OpenData_resources(); //using OpenData API --- working OK!
elseif($resource_id == 'process_all_resources')      $func->process_all_resources(); //using CONTENT_RESOURCE_LOCAL_PATH --- working OK!
elseif($resource_id)                                 $func->scan_a_resource($resource_id);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>