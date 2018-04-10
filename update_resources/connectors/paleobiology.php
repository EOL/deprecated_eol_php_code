<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PaleobiologyAPI');

exit("\nThis seems obsolete already.\n");
$paleobiology_connector = new PaleobiologyAPI;
$paleobiology_connector->get_all_taxa();

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "paleobiology_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . "paleobiology"))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "paleobiology_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "paleobiology", CONTENT_RESOURCE_LOCAL_PATH . "paleobiology_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . "paleobiology_working", CONTENT_RESOURCE_LOCAL_PATH . "paleobiology");
    #$GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Harvest Requested')->id." WHERE id=6");
}


?>
