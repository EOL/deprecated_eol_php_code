<?php
namespace php_active_record;
/* DATA-1844 
Tuesday 2019-12-17 12:36:49 PM	{"measurement_or_fact_specific.tab":13119,"occurrence_specific.tab":13119,"taxon.tab":5250,"time_elapsed":{"sec":12058.37,"min":200.97,"hr":3.35}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConservationEvidenceDataAPI');
$timestart = time_elapsed();
$resource_id = "con_evi"; //for "Conservation Evidence" resource
$func = new ConservationEvidenceDataAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>
