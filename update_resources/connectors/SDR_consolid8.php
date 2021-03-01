<?php
namespace php_active_record;
/*
php update_resources/connectors/SDR_consolid8.php _ parent_BV_consolid8
php update_resources/connectors/SDR_consolid8.php _ TS_consolid8
php update_resources/connectors/SDR_consolid8.php _ parent_TS_consolid8
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$resource_id                 = @$argv[2]; //useful here


// /* //main operation
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','9096M'); //required

//initialize
$input['parent_BV_consolid8']['dwca'] = CONTENT_RESOURCE_LOCAL_PATH.'parent_basal_values.tar.gz';
$input['parent_BV_consolid8']['dwca'] = 'https://editors.eol.org/other_files/SDR/parent_basal_values.tar.gz';
$input['TS_consolid8']['dwca'] = CONTENT_RESOURCE_LOCAL_PATH.'taxon_summary.tar.gz';
$input['TS_consolid8']['dwca'] = 'https://editors.eol.org/other_files/SDR/taxon_summary.tar.gz';
$input['parent_TS_consolid8']['dwca'] = CONTENT_RESOURCE_LOCAL_PATH.'parent_taxon_summary.tar.gz';
$input['parent_TS_consolid8']['dwca'] = 'https://editors.eol.org/other_files/SDR/parent_taxon_summary.tar.gz';
//end initialize

$dwca = $input[$resource_id]['dwca'];
$func = new DwCA_Utility($resource_id, $dwca);
$preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/occurrence');
$func->convert_archive($preferred_rowtypes);
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);

//diagnosing...
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //2nd param true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n";
// */
?>