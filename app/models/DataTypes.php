<?php
namespace php_active_record;

class DataType extends ActiveRecord
{
    public static $before_create = array(
            'set_schema_value'
        );
    
    public function set_schema_value()
    {
        if(@!$this->schema_value) $this->schema_value = $this->label;
    }
    
    public static function image()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/StillImage');
    }
    
    public static function sound()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/Sound');
    }
    
    public static function text()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/Text');
    }
    
    public static function video()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/MovingImage');
    }
    
    public static function iucn()
    {
        return DataType::find_or_create_by_schema_value('IUCN');
    }
    
    public static function flash()
    {
        return DataType::find_or_create_by_schema_value('Flash');
    }
    
    public static function youtube()
    {
        return DataType::find_or_create_by_schema_value('YouTube');
    }

    public static function map()
    {
        return DataType::find_or_create_by_schema_value('Map');
    }
    
    public static function sound_type_ids()
    {
        return array(DataType::sound()->id);
    }

    public static function image_type_ids()
    {
        return array(DataType::image()->id);
    }

    public static function video_type_ids()
    {
        return array(DataType::youtube()->id, DataType::flash()->id, DataType::video()->id);
    }

    public static function map_type_ids()
    {
        return array(DataType::map()->id);
    }

    public static function text_type_ids()
    {
        return array(DataType::text()->id);
    }
    
}

?>