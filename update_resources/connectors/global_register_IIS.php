<?php
namespace php_active_record;
/* Global Register of Introduced and Invasive Species : DATA-1838
e.g. Belgium
https://www.gbif.org/dataset/6d9e952f-948c-4483-9807-575348147c7e
https://api.gbif.org/v1/dataset/6d9e952f-948c-4483-9807-575348147c7e/document

e.g.
Belgium -- https://ipt.inbo.be/archive.do?r=unified-checklist
South Africa -- http://ipt.ala.org.au/archive.do?r=south-africa-griis-gbif

griis	Friday 2019-10-18 04:29:15 AM	{"measurement_or_fact_specific.tab":91634,"occurrence_specific.tab":91634,"taxon.tab":51907,"time_elapsed":{"sec":744.11,"min":12.4,"hr":0.21}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies');
$timestart = time_elapsed();
$cmdline_params['jenkins_or_cron'] = @$argv[1]; //irrelevant here

/* test
$south = array('a','b','c');
$belgium = array('a','b','c','d','e');
$diff = array_diff($belgium, $south); //proper
// $diff = array_diff($south, $belgium); //can be used to check what is in others but South Africa doesn't have
print_r($diff); exit;
*/

$resource_id = 'griis'; //Global Register of Introduced and Invasive Species
$func = new GlobalRegister_IntroducedInvasiveSpecies($resource_id);

/* worked OK
$func->compare_meta_between_datasets(); //a utility to generate report for Jen
$func->start(true); //utility, generate report for Jen. Used once only. The 'true' param makes the call to generate a report.
*/

// /*
$func->start(); //main operation - generate DwCA
// */

Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
?>