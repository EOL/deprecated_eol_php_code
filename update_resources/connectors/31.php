<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BiopixAPI');


$biopix_connector = new BiopixAPI;
$biopix_connector->get_all_taxa();

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "31_working/taxon.tab") > 1000 &&
    filesize(CONTENT_RESOURCE_LOCAL_PATH . "31_working/media_resource.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . "31"))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "31_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "31", CONTENT_RESOURCE_LOCAL_PATH . "31_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "31_working", CONTENT_RESOURCE_LOCAL_PATH . "31");
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Force Harvest')->id." WHERE id=31");
}


?>
