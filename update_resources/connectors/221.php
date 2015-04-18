<?php
namespace php_active_record;
/*
DATA-695, DATA-1598

EOL XML resource (last XML version)
taxon               = 364
dwc:ScientificName  = 364
commonName          = 735
dataObjects         = 3275
reference           = 315
texts               = 1735
images              = 1540

New DWC-A resource:
                Apr6
agent           [31]
media_resource  [14682]
reference       [58]
taxon           [854]
vernacular_name [779]

- we are now getting synonyms
- we now have separated 'habitat' from 'depth range'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/RosarioBeachMarineLabAPI');
$timestart = time_elapsed();

$resource_id = 221;
$func = new RosarioBeachMarineLabAPI($resource_id);
$func->get_all_taxa($resource_id);
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
    Functions::count_resource_tab_files($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>