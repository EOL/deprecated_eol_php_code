<?php
namespace php_active_record;

class test_rake_tasks extends SimpletestUnitBase
{
    function testForceHarvest()
    {
        $resource = Resource::find_or_create(array('resource_status_id' => ResourceStatus::find_or_create_by_translated_label('Validated')->id));
        // copy the resource file
        copy(DOC_ROOT . "tests/fixtures/files/test_resource.xml", $resource->resource_file_path());
        
        $this->assertTrue($resource->resource_status_id == ResourceStatus::find_or_create_by_translated_label('Validated')->id, 'should start with the right status');
        
        shell_exec(PHP_BIN_PATH.DOC_ROOT."rake_tasks/harvest_requested.php -id $resource->id ENV_NAME=test");
        $resource->refresh();
        $this->assertTrue($resource->resource_status_id == ResourceStatus::find_or_create_by_translated_label('Harvest Requested')->id, 'script should reset the status');
    }
}

?>