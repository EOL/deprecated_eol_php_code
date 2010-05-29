<?php

class Reference extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public function add_ref_identifier($type_id, $value)
    {
        $value = $this->mysqli->escape($value);
        $this->mysqli->insert("INSERT IGNORE INTO ref_identifiers VALUES ($this->id,$type_id,'$value')");
    }
    
    public function publish()
    {
        $this->mysqli->update("UPDATE refs SET published=1, visibility_id=".Visibility::insert("Visible")." WHERE id=$this->id");
    }
    
    static function insert($string)
    {
        return parent::insert_into("full_reference", $string, Functions::class_name(__FILE__));
    }
    
    static function find($string)
    {
        return parent::find_by("full_reference", $string, Functions::class_name(__FILE__));
    }
}

?>