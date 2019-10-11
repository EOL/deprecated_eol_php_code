<?php
namespace php_active_record;
/* Global Register of Introduced and Invasive Species : DATA-1838
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies');
$timestart = time_elapsed();

$cmdline_params['jenkins_or_cron']     = @$argv[1]; //irrelevant here
// print_r($cmdline_params);

/* local
$params["dwca_file"]     = "http://127.0.0.1/cp_new/GBIF_dwca/countries/Germany_0010139-190918142434337.zip";
*/

// remote
$params["dwca_file"]     = "https://editors.eol.org/other_files/GBIF_DwCA/Germany_0010139-190918142434337.zip";
$params["resource_id"]  = 872;

$resource_id = $params["resource_id"];
$func = new GlobalRegister_IntroducedInvasiveSpecies($resource_id);
$func->start($params);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>