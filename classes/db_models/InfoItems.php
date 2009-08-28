<?php

class InfoItem extends MysqlBase
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
        
        if($result = InfoItem::find($string)) return $result;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $string = $mysqli->escape($string);
        $id = $mysqli->insert("INSERT INTO info_items VALUES (NULL, '$string', '', 0)");
        $GLOBAL['table_ids']['info_items'][$string] = $id;
        
        return $id;
    }
    
    static function find($string)
    {
        return parent::find_by("schema_value", $string, Functions::class_name(__FILE__));
    }
}

?>