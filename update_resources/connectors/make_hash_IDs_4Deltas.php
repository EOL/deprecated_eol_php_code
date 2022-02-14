<?php
namespace php_active_record;
/* This can be a template for any Delta resource, a means to hash identifiers (DATA-1903)
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"71"}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false; //orig value should be -> false
// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */
ini_set('memory_limit','7096M');
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$resource = $param['resource'];

echo "\n========== START hash identifiers ==========\n";
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
process_resource_url($dwca_file, $resource_id."_delta", $timestart, $param);
echo "\n========== END hash identifiers ==========\n";

function process_resource_url($dwca_file, $resource_id, $timestart, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);

    /* Orig in meta.xml has capital letters. Just a note reminder. */
    $excluded_rowtypes = false;
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon','http://eol.org/schema/agent/agent');
    
    /* This will be processed in DeltasHashIDsAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>