<?php

class CommonName extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    static function language()
    {
        if(@$this->language) return $this->language;
        
        $this->language = new Language($this->language_id);
        return $this->language;
    }
    
    static function insert($parameters)
    {
        if(!$parameters) return 0;
        
        if(get_class($parameters)=="CommonName")
        {
            if($result = self::find_by_mock_object($parameters)) return $result;
            return parent::insert_object_into($parameters, Functions::class_name(__FILE__));
        }
        
        if($result = self::find($string, $language_id)) return $result;
        return parent::insert_fields_into(array('common_name' => $string, 'language_id' => $language_id), Functions::class_name(__FILE__));
    }
    
    function find($string, $language_id)
    {
        $string = trim($string);
        if(!$string) return 0;
        $table = Functions::class_name(__FILE__);
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if($cache = Cache::get('table_ids_'.$table.'_'.$string.'_'.$language_id)) return $cache;
        
        $id = 0;
        $string = $mysqli->escape($string);
        $result = $mysqli->query("SELECT id FROM common_names WHERE common_name='$string' AND language_id=$language_id");
        if($result && $row=$result->fetch_assoc()) $id = $row["id"];
        
        if(cache_model($table)) Cache::set('table_ids_'.$table.'_'.$string.'_'.$language_id, $id);
        
        return $id;
    }
    
    static function find_by_mock_object($mock)
    {
        return parent::find_by_mock_obj($mock, Functions::class_name(__FILE__));
    }
}

?>