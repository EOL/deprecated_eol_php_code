<?php
namespace php_active_record;
/*
php update_resources/connectors/SDR_consolid8.php _ parent_BV_consolid8
php update_resources/connectors/SDR_consolid8.php _ TS_consolid8
php update_resources/connectors/SDR_consolid8.php _ parent_TS_consolid8
php update_resources/connectors/SDR_consolid8.php _ SDR_consolidated

in jenkins.eol.org:
php5.6 SDR_consolid8.php jenkins parent_BV_consolid8
php5.6 SDR_consolid8.php jenkins TS_consolid8
php5.6 SDR_consolid8.php jenkins parent_TS_consolid8
php5.6 SDR_consolid8.php jenkins SDR_consolidated

SDR_consolidated	Tue 2021-03-02 11:22:42 AM	{"association_specific.tab":25078, "measurement_or_fact_specific.tab":1964045, "occurrence.tab":312630, "taxon.tab":90371, "time_elapsed":{"sec":1312.96, "min":21.88, "hr":0.36}} Mac Mini
SDR_consolidated	Tue 2021-03-02 11:23:36 AM	{"association_specific.tab":25078, "measurement_or_fact_specific.tab":1964045, "occurrence.tab":312630, "taxon.tab":90371, "time_elapsed":{"sec":769.91, "min":12.83, "hr":0.21}} eol-archive

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$resource_id                 = @$argv[2]; //useful here

if($resource_id == 'SDR_consolidated')
{
    require_library('connectors/SDR_Consolid8API');
    $func = new SDR_Consolid8API(false, $resource_id);
    $func->consolidate_all_reports();
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
    return;
}

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