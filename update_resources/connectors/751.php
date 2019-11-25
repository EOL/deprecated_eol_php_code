<?php
namespace php_active_record;
/* execution time:  14 mins eol-archive 
751	Thursday 2018-08-02 09:18:48 AM	{"measurement_or_fact.tab":20770,"occurrence.tab":20770,"taxon.tab":868}    eol-archive
751	Monday 2019-11-25 10:51:43 AM	{"measurement_or_fact.tab":20768,"occurrence.tab":20768,"taxon.tab":868,"time_elapsed":{"sec":1814.62,"min":30.24,"hr":0.5}} Consistent OK
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/InvasiveSpeciesDataConnector');
$timestart = time_elapsed();
$resource_id = 751;
$func = new InvasiveSpeciesDataConnector($resource_id, "GISD");
$func->generate_invasiveness_data();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>