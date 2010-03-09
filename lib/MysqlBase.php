<?php

class MysqlBase
{
    protected $mysqli;
    
    function db_connect()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    function initialize($param = NULL)
    {
        $this->db_connect();
        
        $row = false;
        if(is_array($param))
        {
            $row = $param;
            if(cache_model($this->table_name)) Cache::set('class_instances_'.$this->table_name.'_'.serialize($param), $row);
        }elseif($param)
        {
            if($cache = Cache::get('class_instances_'.$this->table_name.'_'.serialize($param))) $row = $cache;
            
            if(!$row)
            {
                $result = $GLOBALS['db_connection']->query("SELECT SQL_NO_CACHE * FROM ".$this->table_name." WHERE id=$param");
                if($result && $result->num_rows)
                {
                    $row = $result->fetch_assoc();
                    if(cache_model($this->table_name)) Cache::set('class_instances_'.$this->table_name.'_'.serialize($param), $row);
                }
            }
        }
        
        if($row)
        {
            foreach($row as $k => $v) $this->$k = $v;
        }
    }
    
    function get_table_fields()
    {
        return table_fields($this->table_name);
    }
    
    function insert_into($field, $string, $table)
    {
        $field = trim($field);
        $string = trim($string);
        $table = trim($table);
        if(!$field) return null;
        if(!$string) return null;
        if(!$table) return null;
        
        $result = self::find_by($field, $string, $table);
        if($result !== null) return $result;
        
        $string = $GLOBALS['db_connection']->escape($string);
        $id = $GLOBALS['db_connection']->insert("INSERT INTO $table (`$field`) VALUES ('$string')");
        
        return $id;
    }
    
    function insert_fields_into($fields, $table)
    {
        $table = trim($table);
        if(!$table) return null;
        if(!$fields) return null;
        if(!is_array($fields)) return null;
        
        foreach($fields as $k => $v) $fields[$k] = $GLOBALS['db_connection']->escape($v);
        
        $query = "INSERT INTO $table (`";
        $query .= implode("`, `", array_keys($fields));
        $query .= "`) VALUES ('";
        $query .= implode("', '", $fields);
        $query .= "')";
        
        $id = $GLOBALS['db_connection']->insert($query);
        
        return $id;
    }
    
    function insert_object_into($object, $table)
    {
        $table = trim($table);
        if(!$table) return null;
        if(!$object) return null;
        if(get_parent_class($object) != "MysqlBase") return null;
        
        $parameters = array();
        $fields = $object->get_table_fields();
        foreach($fields as $field)
        {
            if(@$object->$field) $parameters[$field] = $GLOBALS['db_connection']->escape($object->$field);
        }
        
        $query = "INSERT INTO $table (`";
        $query .= implode("`, `", array_keys($parameters));
        $query .= "`) VALUES ('";
        $query .= implode("', '", $parameters);
        $query .= "')";
        
        $id = $GLOBALS['db_connection']->insert($query);
        
        return $id;
    }
    
    function find_by($field, $string, $table)
    {
        $field = trim($field);
        $string = trim($string);
        $table = trim($table);
        if(!$field) return null;
        if(!$string) return null;
        if(!$table) return null;
        
        if($cache = Cache::get('find_by_ids_'.$table.'_'.$field.'_'.$string)) return $cache;
        
        $id = null;
        $string = $GLOBALS['db_connection']->escape($string);
        $result = $GLOBALS['db_connection']->query("SELECT SQL_NO_CACHE id FROM $table WHERE $field='$string'");
        if($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];
            if(cache_model($table)) Cache::set('find_by_ids_'.$table.'_'.$field.'_'.$string, $id);
        }
        if($result && $result->num_rows) $result->free();
        
        return $id;
    }
    
    static function find_by_id_base($field, $id, $table)
    {
        $field = trim($field);
        $table = trim($table);
        if(!$field) return null;
        if(!$id) return null;
        if(!$table) return null;
        
        if($cache = Cache::get('tables_find_by_id_'.$table.'_'.$field.'_'.$id)) return $cache;
        
        $result = $GLOBALS['db_connection']->query("SELECT SQL_NO_CACHE $field FROM $table WHERE id=$id");
        if($result && $row=$result->fetch_assoc())
        {
            $field = $row[$field];
            if(cache_model($table)) Cache::set('tables_find_by_id_'.$table.'_'.$field.'_'.$id, $field);
            return $field;
        }
        if($result && $result->num_rows) $result->free();
        
        return false;
    }
    
    function find_by_mock_obj($object, $table)
    {
        $table = trim($table);
        if(!$table) return null;
        if(!$object) return null;
        if(get_parent_class($object) != "MysqlBase") return null;
        
        $query_parameters = array();
        $fields = $object->get_table_fields();
        foreach($fields as $field)
        {
            if(@$object->$field) $query_parameters[] = "$field='".$GLOBALS['db_connection']->escape($object->$field)."'";
        }
        if(!$query_parameters) return null;
        
        $query = "SELECT SQL_NO_CACHE id FROM $table WHERE ";
        $query .= implode(" AND ", $query_parameters);
        
        $id = null;
        $result = $GLOBALS['db_connection']->query($query);
        if($result && $row = $result->fetch_assoc())
        {
            $id = $row["id"];
        }
        if($result && $result->num_rows) $result->free();
        
        return $id;
    }
    
    static function find_all_by($table, $order = "id", $field = "1", $value = "1")
    {
        $ids = array();
        
        $result = $GLOBALS['db_connection']->query("SELECT SQL_NO_CACHE id FROM $table WHERE $field = $value ORDER BY $order");
        while($result && $row = $result->fetch_assoc())
        {
            $ids[] = $row["id"];
        }
        
        return $ids;
    }
    
    function delete_base($id, $table)
    {
        $GLOBALS['db_connection']->delete("DELETE FROM $table WHERE id=$id");
    }
    
    public function __toString()
    {
        return Functions::print_r_public($this, true);
    }
}

?>