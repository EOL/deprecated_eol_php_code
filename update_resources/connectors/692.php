<?php
namespace php_active_record;
/* OBIS Environmental Information
execution time: 1.11 hours

692	Saturday 2018-03-10 08:24:23 PM	{"measurement_or_fact.tab":1924574,"occurrence.tab":486564,"taxon.tab":162188} - without measurementID - MacMini
692	Sunday 2018-03-11 06:13:34 AM	{"measurement_or_fact.tab":1924574,"occurrence.tab":486564,"taxon.tab":162188} - with measurementID - MacMini
692	Sunday 2018-03-11 08:26:06 AM	{"measurement_or_fact.tab":1924554,"occurrence.tab":486561,"taxon.tab":162187} - eol-archive, start with measurementID
692	Monday 2019-12-02 08:37:49 AM	{"measurement_or_fact.tab":1924554,"occurrence.tab":486561,"taxon.tab":162187,"time_elapsed":{"sec":741.5,"min":12.36,"hr":0.21}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ObisDataConnector');
ini_set('memory_limit','5096M'); //needed so it can process checking of identifier uniqueness in measurement and occurrence extensions.
$timestart = time_elapsed();
$resource_id = 692;
$connector = new ObisDataConnector($resource_id);
$connector->build_archive();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means will delete folder resource name /692/ in CONTENT_RESOURCE_LOCAL_PATH
?>