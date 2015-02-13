<?php
namespace php_active_record;
/* https://jira.eol.org/browse/DATA-1549 iDigBio Portal 
                5k                  4Feb
measurement     6748    1385056     3191194
occurrence      2250    461686      461686
taxon           2157    224065      224065
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFCountryTypeRecordAPI');
$timestart = time_elapsed();

$params["dwca_file"]    = "http://localhost/~eolit/cp/iDigBio/iDigBioTypes.zip";
$params["uri_file"]     = "https://dl.dropboxusercontent.com/u/7597512/iDigBio/idigbio mappings.xlsx";
$params["uri_file"]     = "http://localhost/~eolit/cp/iDigBio/idigbio mappings.xlsx";
$params["dataset"]      = "iDigBio";
$params["type"]         = "structured data";
$params["resource_id"]  = 885;

$resource_id = $params["resource_id"];
$func = new GBIFCountryTypeRecordAPI($resource_id);
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