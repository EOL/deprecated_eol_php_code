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
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/StillImage', array('label' => 'Image'));
    }
    
    public static function sound()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/Sound', array('label' => 'Sound'));
    }
    
    public static function text()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/Text', array('label' => 'Text'));
    }
    
    public static function video()
    {
        return DataType::find_or_create_by_schema_value('http://purl.org/dc/dcmitype/MovingImage', array('label' => 'Video'));
    }
    
    public static function iucn()
    {
        return DataType::find_or_create_by_schema_value('IUCN', array('label' => 'IUCN'));
    }
    
    public static function flash()
    {
        return DataType::find_or_create_by_schema_value('Flash', array('label' => 'Flash'));
    }
    
    public static function youtube()
    {
        return DataType::find_or_create_by_schema_value('YouTube', array('label' => 'YouTube'));
    }
    
    
}

?>