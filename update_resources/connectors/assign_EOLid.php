<?php
namespace php_active_record;
/* This can be a template to update a resource's taxon.tab file by adding an EOLid
First client: wikidata-hierarchy-final.tar.gz

php update_resources/connectors/assign_EOLid.php _ '{"resource_id": "wikidata-hierarchy-final"}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */
// ini_set('memory_limit','7096M');
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;
$resource_id = $param['resource_id'];

$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
// $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz'; //during dev only

if($resource_id == "wikidata-hierarchy-final") $resource_id = "wikidata_hierarchy";
else exit("\nresource_id [$resource_id] not yet initialized. \n");

process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    $preferred_rowtypes = false;
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');

    /* This will be processed in DwCA_AssignEOLidAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>