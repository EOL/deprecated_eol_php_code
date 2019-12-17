<?php
namespace php_active_record;
/* DATA-1844 

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConservationEvidenceDataAPI');
$timestart = time_elapsed();
$resource_id = "con_evi"; //for "Conservation Evidence" resource
$func = new ConservationEvidenceDataAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
?>
