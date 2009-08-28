<?php

class CleanName extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    static function insert($name_id, $string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        if($result = self::find($string)) return $result;
        return parent::insert_fields_into(array('name_id' => $name_id, 'clean_name' => $string), Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        $string = trim($string);
        if(!$string) return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if(isset($GLOBAL['table_ids']['clean_names'][$string])) return $GLOBAL['table_ids']['clean_names'][$string];
        
        $name_id = 0;
        $string = $mysqli->escape($string);
        $result = $mysqli->query("SELECT name_id FROM clean_names WHERE clean_name='$string'");
        if($result && $row=$result->fetch_assoc()) $name_id = $row["name_id"];
        
        $GLOBAL['table_ids']['clean_names'][$string] = $name_id;
        
        return $name_id;
    }
}

?>