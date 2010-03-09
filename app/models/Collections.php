<?php

class Collection extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
    }
    
    public function add_mapping($name_string, $foreign_key)
    {
        $name_id = Name::insert($name_string);
        
        $mock_mapping = Functions::mock_object("Mapping", array("collection_id" => $this->id, "name_id" => $name_id, "foreign_key" => $foreign_key));
        $mapping_id = Mapping::insert($mock_mapping);
    }
    
    public function add_mapping_by_name_id($name_id, $foreign_key)
    {
        $mock_mapping = Functions::mock_object("Mapping", array("collection_id" => $this->id, "name_id" => $name_id, "foreign_key" => $foreign_key));
        $mapping_id = Mapping::insert($mock_mapping);
    }
    
    static function insert($parameters)
    {
        if(!$parameters) return 0;
        
        if(get_class($parameters)=="Collection")
        {
            if($result = self::find_by_mock_object($parameters)) return $result;
            return parent::insert_object_into($parameters, Functions::class_name(__FILE__));
        }
        
        return 0;
    }
    
    static function find_by_mock_object($mock)
    {
        return parent::find_by_mock_obj($mock, Functions::class_name(__FILE__));
    }
}

?>