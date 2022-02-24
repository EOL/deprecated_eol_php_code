<?php
namespace php_active_record;
/* This can be a template for any Delta resource, a means to hash identifiers (DATA-1903)

php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"71"}' //Wikimedia commons
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"15"}' //Flickr
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"368_cleaned_MoF"}' //PaleoDB

php5.6 make_hash_IDs_4Deltas.php jenkins '{"task": "", "resource":"Deltas_4hashing", "resource_id":"71"}'
php5.6 make_hash_IDs_4Deltas.php jenkins '{"task": "", "resource":"Deltas_4hashing", "resource_id":"15"}'
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
if(in_array($resource_id, array("71", "15", "368_cleaned_MoF"))) {
    $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
}
else exit("\nNot yet initialized [$resource_id]\n");

// /* customize
if($resource_id == "368_cleaned_MoF") $resource_id = "368";
// */

process_resource_url($dwca_file, $resource_id."_delta", $timestart, $param);
echo "\n========== END hash identifiers ==========\n";

function process_resource_url($dwca_file, $resource_id, $timestart, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);

    /* Orig in meta.xml has capital letters. Just a note reminder. */
    if(in_array($resource_id, array("71_delta", "15_delta"))) {
        $excluded_rowtypes = false;
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon','http://eol.org/schema/agent/agent');
    }
    elseif(in_array($resource_id, array("368_delta"))) {
        $excluded_rowtypes = false;
        $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon','http://rs.gbif.org/terms/1.0/vernacularname');
    }
    else exit("\nNot yet initialized [$resource_id]\n");
    
    /* This will be processed in DeltasHashIDsAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>