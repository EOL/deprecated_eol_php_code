<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
require_library('connectors/ConsolidateTMReportsAPI');
$timestart = time_elapsed();

/* e.g. php consolidate_tm_reports.php _ saproxylic */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$SearchTerm                         = @$argv[2]; //useful here

$resource_id = $SearchTerm;
$func = new ConsolidateTMReportsAPI($resource_id, $SearchTerm);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param false means not delete folder
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>