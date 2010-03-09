<?php

class TableOfContents extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public function rename($label)
    {
        $result = $this->mysqli->update("UPDATE table_of_contents SET label='".$this->mysqli->escape($label)."' WHERE id=$this->id");
    }
    
    static function roots()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $children = array();
        
        $result = $mysqli->query("SELECT * FROM table_of_contents WHERE parent_id=0 ORDER BY view_order ASC");
        while($result && $row=$result->fetch_assoc()) $children[] = new TableOfContents($row);
        $result->free();
        
        return $children;
    }
    
    public function children()
    {
        $children = array();
        
        $result = $this->mysqli->query("SELECT * FROM table_of_contents WHERE parent_id=".$this->id." ORDER BY view_order ASC");
        while($result && $row=$result->fetch_assoc()) $children[] = new TableOfContents($row);
        $result->free();
        
        return $children;
    }
    
    public function add_child($label)
    {
        $view_order = $this->next_view_order();
        
        $params = array();
        $params["parent_id"] = $this->id;
        $params["label"] = $label;
        $params["view_order"] = $view_order;
        
        return self::insert($params);
    }
    
    public function next_view_order()
    {
        $result = $this->mysqli->query("SELECT MAX(view_order) as max FROM table_of_contents WHERE parent_id=$this->id");
        if($result && $row=$result->fetch_assoc()) return $row["max"]+1;
        
        return 1;
    }
    
    static function add_root($label)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $view_order = 1;
        $result = $mysqli->query("SELECT MAX(view_order) as max FROM table_of_contents WHERE parent_id=0");
        if($result && $row=$result->fetch_assoc()) $view_order = $row["max"] + 1;
        
        $params = array();
        $params["parent_id"] = 0;
        $params["label"] = $label;
        $params["view_order"] = $view_order;
        
        return self::insert($params);
    }
    
    static function insert($parameters)
    {
        if(!$parameters) return 0;
        
        if($result = self::find($parameters)) return $result;
        
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($parameters)
    {
        return 0;
    }
    
    function delete()
    {
        parent::delete_base($this->id, Functions::class_name(__FILE__));
        $children = $this->children();
        foreach($children as $child)
        {
            parent::delete_base($child->id, Functions::class_name(__FILE__));
        }
    }
}

?>