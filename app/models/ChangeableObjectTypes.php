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

    public static function curated_data_objects_hierarchy_entry()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('curated_data_objects_hierarchy_entry');
    }

    public static function users_data_object()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('users_submitted_text');
    }

    public static function data_object_scope()
    {
        return array(self::data_object()->id, 
                     self::data_objects_hierarchy_entry()->id, 
                     self::curated_data_objects_hierarchy_entry()->id, 
                     self::users_data_object()->id);
    }

    public static function synonym()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('synonym');
    }

    public static function comment()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('comment');
    }

    public static function taxon_concept()
    {
        return ChangeableObjectType::find_or_create_by_ch_object_type('taxon_concept');
    }


}

?>
