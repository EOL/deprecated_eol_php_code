<?php
namespace php_active_record;

class ResourceStatus extends ActiveRecord
{
    public static function uploading()
    {
        return ResourceStatus::find_or_create_by_label('Uploading', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function uploaded()
    {
        return ResourceStatus::find_or_create_by_label('Uploaded', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function upload_failed()
    {
        return ResourceStatus::find_or_create_by_label('Upload Failed', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function moved()
    {
        return ResourceStatus::find_or_create_by_label('Moved to Content Server', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function validated()
    {
        return ResourceStatus::find_or_create_by_label('Validated', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function validation_failed()
    {
        return ResourceStatus::find_or_create_by_label('Validation Failed', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function being_processed()
    {
        return ResourceStatus::find_or_create_by_label('Being Processed', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function processed()
    {
        return ResourceStatus::find_or_create_by_label('Processed', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function processing_failed()
    {
        return ResourceStatus::find_or_create_by_label('Processing Failed', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function published()
    {
        return ResourceStatus::find_or_create_by_label('Published', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function publish_pending()
    {
        return ResourceStatus::find_or_create_by_label('Publish Pending', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function unpublish_pending()
    {
        return ResourceStatus::find_or_create_by_label('Unpublish Pending', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function force_harvest()
    {
        return ResourceStatus::find_or_create_by_label('Force Harvest', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
}

?>