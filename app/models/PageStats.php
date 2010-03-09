<?php

class PageStat extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    static function find_all()
    {
        return parent::find_all_by(Functions::class_name(__FILE__), "date_created desc, time_created desc");
    }
    
    static function insert($string)
    {
        return parent::insert_into("label", $string, Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("label", $string, Functions::class_name(__FILE__));
    }
}

?>