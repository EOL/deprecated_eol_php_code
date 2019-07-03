<?php
namespace php_active_record;
/*
execution time: 3 hours when HTTP request is already cached
Connector processes a CSV file exported from the IUCN portal (www.iucnredlist.org). 
The exported CSV file is requested and is generated by the portal a couple of days afterwards.
The completion is confirmed via email to the person who requested it.

To be harvestd quarterly: https://jira.eol.org/browse/WEB-5427
#==== 8 PM, 25th of the month, quarterly (Feb, May, Aug, Nov) => IUCN Structured Data
00 20 25 2,5,8,11 * /usr/bin/php /opt/eol_php_code/update_resources/connectors/737.php > /dev/null

            taxon   measurementorfact   occurrence
2014 05 27  73,465  533,549
2014 08 14  76,022  554,047
2016 08 25  81,703  597,586             243,525
2017 10 05  80,823  591,236             240,800

737	Friday 2018-03-02 04:19:52 PM	{"measurement_or_fact.tab":591236,"occurrence.tab":240800,"taxon.tab":80823}
737	Wednesday 2018-03-07 11:16:22 AM{"measurement_or_fact.tab":591236,"occurrence.tab":240800,"taxon.tab":80823}
737	Wednesday 2018-03-07 07:04:07 PM{"measurement_or_fact.tab":591236,"occurrence.tab":240800,"taxon.tab":80823} all-hash measurementID
737	Thursday 2018-03-08 08:10:12 PM	{"measurement_or_fact.tab":591236,"occurrence.tab":240800,"taxon.tab":80823}
*/

exit("\nObsolete. We are now using their API - IUCNRedlistUsingAPI.php\n");

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning

require_library('connectors/IUCNRedlistDataConnector');
$timestart = time_elapsed();
$resource_id = 737;
if(!Functions::can_this_connector_run($resource_id)) return;

// /* NOTE: like 211.php a manual step is needed to update partner source file (export-74550.csv.zip)
$func = new IUCNRedlistDataConnector($resource_id);
$func->generate_IUCN_data();
Functions::finalize_dwca_resource($resource_id, false, true);
// */

Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>