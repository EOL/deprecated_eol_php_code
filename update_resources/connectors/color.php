<?php
namespace php_active_record;
/* DATA-1416 Flower color from EOL photos, Chantal Marie Wright
measurement     [1165]
occurrence      [1165]
taxon           [183]
vernacular      [147]
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/FlowerColorAPI');
$timestart = time_elapsed();
$params["resource_id"] = 1;
$params["spreadsheet_file"] = "http://localhost/~eolit/cp/FlowerColor/master analysis color with PATO values.xlsx";
$params["spreadsheet_file"] = "https://dl.dropboxusercontent.com/u/7597512/FlowerColor/master analysis color with PATO values.xlsx";
$func = new FlowerColorAPI($params);
$func->get_all_taxa();
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"]))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"], CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_working", CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"]);
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $params["resource_id"] . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($params["resource_id"]);
    Functions::count_resource_tab_files($params["resource_id"]);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>