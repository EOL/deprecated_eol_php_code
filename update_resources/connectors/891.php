<?php
namespace php_active_record;
/* WEB-5843 Import Smithsonian type specimen data to TraitBank

                    14Jan
measurement_or_fact 4,768,256
occurrence          468,454
taxon               295903

10k records:        9Jan
measurement_or_fact 9218
occurrence          1708
taxon               716
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHTypeRecordAPI');
$timestart = time_elapsed();

//local source
$params["dwca_file"]    = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/dwca-nmnhdwca.zip";
$params["uri_file"]     = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/nmnh mappings.xlsx";

//remote source
$params["dwca_file"]    = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/dwca-nmnhdwca.zip";
$params["dwca_file"]    = "http://collections.mnh.si.edu/ipt/archive.do?r=nmnhdwca"; // true value - working
$params["uri_file"]     = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/nmnh mappings.xlsx";

$params["dataset"]      = "NMNH";
$params["type"]         = "structured data";
$params["resource_id"]  = 891;

$resource_id = $params["resource_id"];
$func = new NMNHTypeRecordAPI($resource_id);
$func->export_gbif_to_eol($params);
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