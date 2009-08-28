<?php

class License extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    static function insert($string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        if($result = self::find($string)) return $result;
        return parent::insert_fields_into(array('source_url' => $string), Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("source_url", $string, Functions::class_name(__FILE__));
    }
}

?>