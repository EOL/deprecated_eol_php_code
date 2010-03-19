<?php

class test_rake_tasks extends SimpletestUnitBase
{
    function testForceHarvest()
    {
        $resource_id = Resource::insert(array('resource_status_id'    => ResourceStatus::insert('Validated')));
        $resource = new Resource($resource_id);
        // copy the resource file
        copy(DOC_ROOT . "tests/fixtures/files/test_resource.xml", $resource->resource_file_path());
        
        $this->assertTrue($resource->resource_status_id == ResourceStatus::insert('Validated'), 'should start with the right status');
        
        shell_exec(PHP_BIN_PATH.DOC_ROOT."rake_tasks/force_harvest.php -id $resource_id ENV_NAME=test");
        $resource = new Resource($resource_id);
        $this->assertTrue($resource->resource_status_id == ResourceStatus::insert('Force Harvest'), 'script should reset the status');
    }
}

?>