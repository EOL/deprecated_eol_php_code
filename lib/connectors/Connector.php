<?php
namespace php_active_record;

class Connector
{
    public $resource_id;
    
    public function __construct()
    {
        
    }
    
    /*
    // cache the previous version and make this new version the current version
    @unlink(CONTENT_RESOURCE_LOCAL_PATH . "15_previous.xml");
    @rename(CONTENT_RESOURCE_LOCAL_PATH . "15.xml", CONTENT_RESOURCE_LOCAL_PATH . "15_previous.xml");
    rename(CONTENT_RESOURCE_LOCAL_PATH . "15_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . "15.xml");
    */
    
    private function set_resource_status_to_harvest_requested()
    {
        // the resource XML response declaration is 516 bytes, so we're checking for something
        // slightly larger than that to make sure we don't have a file with a response
        // and no content
        if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . ".xml") > 600)
        {
            $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::harvest_requested()->id . " WHERE id=" . $this->resource_id);
        }
    }
}

?>