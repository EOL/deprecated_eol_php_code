<?php
namespace php_active_record;
/* WEB-5843 Import Smithsonian type specimen data to TraitBank

                    23Dec
measurement_or_fact 4768256
occurrence          468454
taxon               295903
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHTypeRecordAPI');
$timestart = time_elapsed();

$params["dwca_file"]    = "http://collections.mnh.si.edu/ipt/archive.do?r=nmnhdwca";
$params["dwca_file"]    = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/dwca-nmnhdwca.zip";
$params["dwca_file"]    = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/dwca-nmnhdwca.zip";
$params["uri_file"]     = "http://localhost/~eolit/cp/NMNH/type_specimen_resource/nmnh mappings.xlsx";
$params["uri_file"]     = "https://dl.dropboxusercontent.com/u/7597512/NMNH/type_specimen_resource/nmnh%20mappings.xlsx";
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