<?php
namespace php_active_record;
/* Rotifers
estimated execution time: processing text files exported from MSAccess DB: 2 minutes

as of 2013 10 01:
http://eol.org/schema/reference/reference:Total: 1462
    http://purl.org/dc/dcmitype/Text: 6101
    http://purl.org/dc/dcmitype/StillImage: 2524
    http://www.eol.org/voc/table_of_contents#TypeInformation: 4005
    http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 2096
http://rs.tdwg.org/dwc/terms/taxon:Total: 2101
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/RotifersAPI');

$timestart = time_elapsed();
$resource_id = 660;
$func = new RotifersAPI($resource_id);
// $func->some_stats(); exit;
$func->get_all_taxa();

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
