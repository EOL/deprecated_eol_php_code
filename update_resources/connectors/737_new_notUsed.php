<?php
namespace php_active_record;
/*
https://eol-jira.bibalex.org/browse/DATA-1813
This is now making use of their API
Species list: https://apiv3.iucnredlist.org/api/v3/docs#species
Species count: https://apiv3.iucnredlist.org/api/v3/docs#species-count
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
/*
$GLOBALS['ENV_DEBUG'] = false;
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
*/

require_library('connectors/IUCNRedlistUsingAPI');
$timestart = time_elapsed();
$resource_id = 737;
// if(!Functions::can_this_connector_run($resource_id)) return; //obsolete now...

$func = new IUCNRedlistUsingAPI($resource_id);
$func->generate_IUCN_data();
Functions::finalize_dwca_resource($resource_id, false, true);

// Functions::set_resource_status_to_harvest_requested($resource_id); //obsolete now...

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>