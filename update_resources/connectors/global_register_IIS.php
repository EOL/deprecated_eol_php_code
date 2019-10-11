<?php
namespace php_active_record;
/* Global Register of Introduced and Invasive Species : DATA-1838
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies');
$timestart = time_elapsed();
$cmdline_params['jenkins_or_cron'] = @$argv[1]; //irrelevant here

/* local
$params["dwca_file"]     = "http://127.0.0.1/cp_new/GBIF_dwca/countries/xxx.zip";
*/

// remote
$params["dwca_file"]     = "https://editors.eol.org/other_files/GBIF_DwCA/xxx.zip";

e.g. 
Belgium -- https://ipt.inbo.be/archive.do?r=unified-checklist
South Africa -- http://ipt.ala.org.au/archive.do?r=south-africa-griis-gbif


$resource_id = 'griis'; //Global Register of Introduced and Invasive Species
$func = new GlobalRegister_IntroducedInvasiveSpecies($resource_id);
$func->compare_meta_between_datasets(); //a utility to generate report for Jen

// $func->start($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>