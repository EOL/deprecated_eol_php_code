<?php
namespace php_active_record;
/*
execution time: 11 hours when API calls are already cached
Environments EOL
https://jira.eol.org/browse/DATA-1487
                                Apr2
taxa:               230,808     217,964
measurementorfact:  1,052,641   940,408
occurrence                      940,408
*/
// return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/EnvironmentsEOLDataConnector');
ini_set('memory_limit','7096M'); //required
$timestart = time_elapsed();
$resource_id = 708;
$func = new EnvironmentsEOLDataConnector($resource_id);
$func->generate_EnvEOL_data();
$func = NULL;
Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
// $func->list_folders_with_corrupt_files(); //utility
?>