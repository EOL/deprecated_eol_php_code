<?php

class CanonicalForm extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;

        $this->string = ucfirst($this->string);
    }
    
    static function insert($string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        if($result = self::find($string)) return $result;
        return parent::insert_fields_into(array('string' => $string), Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("string", $string, Functions::class_name(__FILE__));
    }
}

?>