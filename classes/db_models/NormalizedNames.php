<?php

class NormalizedName extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    static function insert($string)
    {
        return parent::insert_into("name_part", $string, Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("name_part", $string, Functions::class_name(__FILE__));
    }
}

?>