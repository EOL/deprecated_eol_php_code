<?php

class Hierarchy extends MysqlBase
{
    static $CatalogueOfLife = 106;
    
    
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }

    public function latest_group_version()
    {
        $result = $this->mysqli->query("SELECT max(hierarchy_group_version) as max FROM hierarchies WHERE hierarchy_group_id=$this->hierarchy_group_id");
        if($result && $row=$result->fetch_assoc()) return $row["max"];
        
        return 0;
    }
    
    static function find_by_agent_id($agent_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT max(id) as max FROM hierarchies WHERE agent_id=$agent_id");
        if($result && $row=$result->fetch_assoc()) return $row["max"];
        
        return 0;
    }
    
    static function find_by_label($string)
    {
        return parent::find_by("label", $string, Functions::class_name(__FILE__));
    }
    
    static function col_2009()
    {
        return self::find_by_label('Species 2000 & ITIS Catalogue of Life: Annual Checklist 2009');
    }
    
    static function next_group_id()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT max(hierarchy_group_id) as max FROM hierarchies");
        if($result && $row=$result->fetch_assoc()) return $row["max"]+1;
        
        return 1;
    }
    
    static function insert($parameters)
    {
        if(get_class($parameters)=="Hierarchy")
        {
            if($result = self::find_by_mock_object($parameters)) return $result;
            return parent::insert_object_into($parameters, Functions::class_name(__FILE__));
        }
        
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find_by_mock_object($mock)
    {
        return parent::find_by_mock_obj($mock, Functions::class_name(__FILE__));
    }
}

?>