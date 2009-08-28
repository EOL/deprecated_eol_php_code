<?php

class Language extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
        
        $this->label = ucfirst($this->label);
    }
    
    static function insert($string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        if($result = self::find($string)) return $result;
        if($result = self::find_by_iso_639_1($string)) return $result;
        if($result = self::find_by_iso_639_2($string)) return $result;
        if($result = self::find_by_iso_639_3($string)) return $result;
        return parent::insert_fields_into(array('label' => $string), Functions::class_name(__FILE__));
    }
    
    static function find_by_iso_639_1($string)
    {
        return parent::find_by("iso_639_1", $string, Functions::class_name(__FILE__));
    }
    
    static function find_by_iso_639_2($string)
    {
        return parent::find_by("iso_639_2", $string, Functions::class_name(__FILE__));
    }
    
    static function find_by_iso_639_3($string)
    {
        return parent::find_by("iso_639_3", $string, Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("label", $string, Functions::class_name(__FILE__));
    }
    
    static function unknown()
    {
        $return = array();
        
        $return[] = Language::find("Undetermined");
        $return[] = Language::find("Unknown");
        $return[] = Language::find("unspecified");
        $return[] = Language::find("Miscellaneous languages");
        $return[] = Language::find("Multiple languages");
        
        return $return;
    }
}

?>