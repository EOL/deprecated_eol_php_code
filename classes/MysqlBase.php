<?php

class MysqlBase
{
    protected $mysqli;
    
    function db_connect()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
    }
    
    function initialize($param = NULL)
    {
        $this->db_connect();
        
        $row = false;
        if(is_array($param))
        {
            $row = $param;
            if(@!$GLOBALS['no_cache'][$this->table_name]) $GLOBALS['class_instances'][$this->table_name][implode("|",array_keys($param))."|=|".implode("|",$param)] = $row;
        }elseif($param)
        {
            $row = @$GLOBALS['class_instances'][$this->table_name][$param];
            
            if(!$row)
            {
                $result = $this->mysqli->query("SELECT SQL_NO_CACHE * FROM ".$this->table_name." WHERE id=$param");
                if($result && $result->num_rows)
                {
                    $row=$result->fetch_assoc();
                    if(@!$GLOBALS['no_cache'][$this->table_name]) $GLOBALS['class_instances'][$this->table_name][$param] = $row;
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
        if(isset($GLOBALS['table_fields'][$this->table_name])) return $GLOBALS['table_fields'][$this->table_name];
        
        $fields = array();
        
        $result = $this->mysqli->query("SHOW fields FROM ".$this->table_name);
        while($result && $row=$result->fetch_assoc())
        {
            $fields[] = $row["Field"];
        }
        if($result) $result->free();
        
        $GLOBALS['table_fields'][$this->table_name] = $fields;
        return $fields;
    }
    
    function insert_into($field, $string, $table)
    {
        $field = trim($field);
        $string = trim($string);
        $table = trim($table);
        if(!$field) return 0;
        if(!$string) return 0;
        if(!$table) return 0;
        
        if($result = self::find_by($field, $string, $table)) return $result;
        $mysqli =& $GLOBALS['mysqli_connection'];
    
        $string = $mysqli->escape($string);
        $id = $mysqli->insert("INSERT INTO $table (`$field`) VALUES ('$string')");
        if(@!$GLOBALS['no_cache'][$table]) $GLOBALS['table_ids'][$table][$field][$string] = $id;
        
        return $id;
    }
    
    function insert_fields_into($fields, $table)
    {
        $table = trim($table);
        if(!$table) return 0;
        if(!$fields) return 0;
        if(!is_array($fields)) return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        foreach($fields as $k => $v) $fields[$k] = $mysqli->escape($v);
        
        $query = "INSERT INTO $table (`";
        $query .= implode("`, `", array_keys($fields));
        $query .= "`) VALUES ('";
        $query .= implode("', '", $fields);
        $query .= "')";
        
        $id = $mysqli->insert($query);
        if(@!$GLOBALS['no_cache'][$table]) $GLOBALS['table_ids'][$table][implode("|",array_keys($fields))."|=|".implode("|",$fields)] = $id;
        
        return $id;
    }
    
    function insert_object_into($object, $table)
    {
        $table = trim($table);
        if(!$table) return 0;
        if(!$object) return 0;
        if(get_parent_class($object) != "MysqlBase") return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $parameters = array();
        $fields = $object->get_table_fields();
        foreach($fields as $field)
        {
            if(@$object->$field) $parameters[$field] = $mysqli->escape($object->$field);
        }
        
        $query = "INSERT INTO $table (`";
        $query .= implode("`, `", array_keys($parameters));
        $query .= "`) VALUES ('";
        $query .= implode("', '", $parameters);
        $query .= "')";
        
        $id = $mysqli->insert($query);
        if(@!$GLOBALS['no_cache'][$table]) $GLOBALS['table_ids'][$table][implode("|",array_keys($parameters))."|=|".implode("|",$parameters)] = $id;
        
        return $id;
    }
    
    function find_by($field, $string, $table)
    {
        $field = trim($field);
        $string = trim($string);
        $table = trim($table);
        if(!$field) return 0;
        if(!$string) return 0;
        if(!$table) return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if(isset($GLOBALS['find_by_ids'][$table][$field][$string])) return $GLOBALS['find_by_ids'][$table][$field][$string];
        
        $id = 0;
        $string = $mysqli->escape($string);
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM $table WHERE $field='$string'");
        if($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];
            if(@!$GLOBALS['no_cache'][$table]) $GLOBALS['find_by_ids'][$table][$field][$string] = $id;
        }
        if($result && $result->num_rows) $result->free();
        
        return $id;
    }
    
    static function find_by_id_base($field, $id, $table)
    {
        $field = trim($field);
        $table = trim($table);
        if(!$field) return 0;
        if(!$id) return 0;
        if(!$table) return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        if(isset($GLOBALS['tables_find_by_id'][$table][$field][$id])) return $GLOBALS['tables_find_by_id'][$table][$field][$id];
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE $field FROM $table WHERE id=$id");
        if($result && $row=$result->fetch_assoc())
        {
            $field = $row[$field];
            if(@!$GLOBALS['no_cache'][$table]) $GLOBALS['tables_find_by_id'][$table][$field][$id] = $field;
            return $field;
        }
        if($result && $result->num_rows) $result->free();
        
        return false;
    }
    
    function find_by_mock_obj($object, $table)
    {
        $table = trim($table);
        if(!$table) return 0;
        if(!$object) return 0;
        if(get_parent_class($object) != "MysqlBase") return 0;
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $query_parameters = array();
        $fields = $object->get_table_fields();
        foreach($fields as $field)
        {
            if(@$object->$field) $query_parameters[] = "$field='".$mysqli->escape($object->$field)."'";
        }
        if(!$query_parameters) return 0;
        
        $query = "SELECT SQL_NO_CACHE id FROM $table WHERE ";
        $query .= implode(" AND ", $query_parameters);
        
        $id = 0;
        $result = $mysqli->query($query);
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
        
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM $table WHERE $field = $value ORDER BY $order");
        while($result && $row = $result->fetch_assoc())
        {
            $ids[] = $row["id"];
        }
        
        return $ids;
    }
    
    function delete_base($id, $table)
    {
        $this->mysqli->delete("DELETE FROM $table WHERE id=$id");
    }
    
    public function __toString()
    {
        $string = "<pre>";
        $string .= print_r($this, true);
        $string .= "</pre>";
        
        return $string;
    }
}

?>