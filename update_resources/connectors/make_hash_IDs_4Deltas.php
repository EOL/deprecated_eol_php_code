<?php
namespace php_active_record;
/* This can be a template for any Delta resource, a means to hash identifiers (DATA-1903)

php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"71"}' //Wikimedia commons
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"15"}' //Flickr
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"368_cleaned_MoF"}' //PaleoDB
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"26_ENV_final"}' //WoRMS
php update_resources/connectors/make_hash_IDs_4Deltas.php _ '{"task": "", "resource":"Deltas_4hashing", "resource_id":"globi_associations_final"}' //GloBI

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
ini_set('memory_limit','18096M'); //orig 7096M --- for GloBi 18096M
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id']; // the source DwCA
$resource = $param['resource'];

echo "\n========== START hash identifiers ==========\n";
if(in_array($resource_id, array("71", "15", "368_cleaned_MoF", "26_ENV_final", "globi_associations_final"))) {
    if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz'; //during dev only
}
else exit("\nNot yet initialized [$resource_id]\n");

// /* customize
if($resource_id == "368_cleaned_MoF")           $resource_id = "368";
if($resource_id == "26_ENV_final")              $resource_id = "26"; # will eventually become 26_delta.tar.gz
if($resource_id == "globi_associations_final")  $resource_id = "globi_associations";
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
    elseif(in_array($resource_id, array("26_delta"))) {
        $excluded_rowtypes = false;
        $preferred_rowtypes = array("http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/media/document", 
                                    "http://eol.org/schema/reference/reference", "http://eol.org/schema/agent/agent", 
                                    "http://rs.gbif.org/terms/1.0/vernacularname");
    }
    elseif(in_array($resource_id, array("globi_associations_delta"))) {
        $excluded_rowtypes = array("http://eol.org/schema/reference/reference"); //too big for DwCA_Utility, will be carried-over
        $excluded_rowtypes[] = "http://rs.tdwg.org/dwc/terms/taxon"; //carried over but also to have unique taxa in taxon.tab
        $excluded_rowtypes[] = "http://rs.tdwg.org/dwc/terms/occurrence";   //for delta hashing
        $excluded_rowtypes[] = "http://eol.org/schema/association";         //for delta hashing
        $preferred_rowtypes = array();
    }
    else exit("\nNot yet initialized 1.0 [$resource_id]\n");
    
    /* This will be processed in DeltasHashIDsAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>