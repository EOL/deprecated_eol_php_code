<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65485&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65485
This script is now used instead of 22.php. See 22.php for more details.
22	Tue 2021-01-05 09:20:19 AM	{                "measurement_or_fact_specific.tab":75723,                             "occurrence_specific.tab":75199, "taxon.tab":6369, "time_elapsed":false}
22	Sat 2021-01-09 12:29:21 PM	{"agent.tab":76, "measurement_or_fact_specific.tab":75723, "media_resource.tab":9264,  "occurrence_specific.tab":75199, "taxon.tab":11014, "time_elapsed":false}
22	Sun 2021-01-10 02:46:21 PM	{"agent.tab":78, "measurement_or_fact_specific.tab":75723, "media_resource.tab":11333, "occurrence_specific.tab":75199, "taxon.tab":11044, "time_elapsed":false}
22	Tue 2021-01-26 01:52:53 PM	{"agent.tab":78, "measurement_or_fact_specific.tab":75723, "media_resource.tab":11333, "occurrence_specific.tab":75199, "taxon.tab":11044, "time_elapsed":false}
22_cleaned_MoF_habitat	Wed 2022-04-13 06:54:54 AM	
                                {"agent.tab":78, "measurement_or_fact_specific.tab":67589, "media_resource.tab":11333, "occurrence_specific.tab":67134, "taxon.tab":11044, "time_elapsed":{"sec":78.56, "min":1.31, "hr":0.02}} Mac Mini
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/QuaardvarkAPI');
$timestart = time_elapsed();
$resource_id = '22';
$func = new QuaardvarkAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>