<?php
namespace php_active_record;

class Status extends ActiveRecord
{
    public static function inserted()
    {
        return Status::find_or_create_by_label('Inserted');
    }
    
    public static function updated()
    {
        return Status::find_or_create_by_label('Updated');
    }
    
    public static function unchanged()
    {
        return Status::find_or_create_by_label('Unchanged');
    }
    
    public static function reused()
    {
        return Status::find_or_create_by_label('Reused');
    }
}

?>