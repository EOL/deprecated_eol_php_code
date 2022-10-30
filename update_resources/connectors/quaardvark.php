<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65485&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65485
This script is now used instead of 22.php. See 22.php for more details.
22	Tue 2021-01-05 09:20:19 AM	                    {                "MoF.tab":75723,                             "occur.tab":75199, "taxon.tab":6369, "time_elapsed":false}
22	Sat 2021-01-09 12:29:21 PM	                    {"agent.tab":76, "MoF.tab":75723, "media.tab":9264,  "occur.tab":75199, "taxon.tab":11014, "time_elapsed":false}
22	Sun 2021-01-10 02:46:21 PM	                    {"agent.tab":78, "MoF.tab":75723, "media.tab":11333, "occur.tab":75199, "taxon.tab":11044, "time_elapsed":false}
22	Tue 2021-01-26 01:52:53 PM	                    {"agent.tab":78, "MoF.tab":75723, "media.tab":11333, "occur.tab":75199, "taxon.tab":11044, "time_elapsed":false}
STILL IN THE WORKS:
22_cleaned_MoF_habitat	Wed 2022-04-13 07:07:11 AM	{"agent.tab":78, "MoF.tab":67589, "media.tab":11333, "occur.tab":67134, "taxon.tab":11044, "time_elapsed":{"sec":71.15, "min":1.19, "hr":0.02}} eol-archive
START PROPER FILTER OF marine+terrestrial in MoF Habitat records: now only contradicting Habitat records are removed
22_cleaned_MoF_habitat	Mon 2022-05-09 06:14:14 AM	{"agent.tab":78, "MoF.tab":74713, "media.tab":11333, "occur.tab":74189, "taxon.tab":11044, "time_elapsed":{"sec":73.62, "min":1.23, "hr":0.02}}
22_cleaned_MoF_habitat	Thu 2022-10-27 01:44:51 AM	{"agent.tab":78, "MoF.tab":74713, "media.tab":11333, "occur.tab":74189, "taxon.tab":11044, "time_elapsed":{"sec":56.81, "min":0.95, "hr":0.02}}

Note: Quaardvark path doesn't use Pensoft annotations.
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