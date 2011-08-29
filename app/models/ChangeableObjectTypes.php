<?php
namespace php_active_record;

class ChangeableObjectType extends ActiveRecord
{
    public static function data_object()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('data_object');
    }
    
    public static function data_objects_hierarchy_entry()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('data_objects_hierarchy_entry');
    }
}

?>