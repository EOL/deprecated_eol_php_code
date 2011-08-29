<?php
namespace php_active_record;

class Visibility extends ActiveRecord
{
    public static function visible()
    {
        return Visibility::find_or_create_by_translated_label('Visible', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function invisible()
    {
        return Visibility::find_or_create_by_translated_label('Invisible', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
    
    public static function preview()
    {
        return Visibility::find_or_create_by_translated_label('Preview', array('created_at' => 'NOW()', 'updated_at' => 'NOW()'));
    }
}

?>