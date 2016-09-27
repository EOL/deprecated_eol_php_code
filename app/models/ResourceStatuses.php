<?php
namespace php_active_record;

class ResourceStatus extends ActiveRecord
{
    public static function uploading()
    {
        return ResourceStatus::find_or_create_by_translated_label('Uploading',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function uploaded()
    {
        return ResourceStatus::find_or_create_by_translated_label('Uploaded',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function upload_failed()
    {
        return ResourceStatus::find_or_create_by_translated_label('Upload Failed',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function moved()
    {
        return ResourceStatus::find_or_create_by_translated_label('Moved to Content Server',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function validated()
    {
        return ResourceStatus::find_or_create_by_translated_label('Validated',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function validation_failed()
    {
        return ResourceStatus::find_or_create_by_translated_label('Validation Failed',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function being_processed()
    {
        return ResourceStatus::find_or_create_by_translated_label('Being Processed',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function processed()
    {
        return ResourceStatus::find_or_create_by_translated_label('Processed',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function processing_failed()
    {
        return ResourceStatus::find_or_create_by_translated_label('Processing Failed',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function published()
    {
        return ResourceStatus::find_or_create_by_translated_label('Published',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

    public static function harvest_requested()
    {
        return ResourceStatus::find_or_create_by_translated_label('Harvest Requested',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

	public static function harvesting_failed()
    {
        return ResourceStatus::find_or_create_by_translated_label('Harvest Failed',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

  public static function harvest_tonight()
    {
        return ResourceStatus::find_or_create_by_translated_label('Harvest Tonight',
          array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }

}

?>
