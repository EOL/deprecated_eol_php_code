<?php

class Agent extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    public function add_resouce($resource_id, $role)
    {
        if(!$resource_id) return 0;
        $this->mysqli->insert("INSERT INTO agents_resources VALUES ($this->id, $resource_id, ".ResourceAgentRole::insert($role).")");
    }
    
    public function update_cache_url($logo_cache_url)
    {
        $this->mysqli->update("UPDATE agents SET logo_cache_url=".$this->mysqli->escape($logo_cache_url)." WHERE id=$this->id");
    }
    
    static function insert($parameters)
    {
        if(!$parameters) return 0;
        
        if(get_class($parameters)=="Agent")
        {
            if($result = self::find_by_mock_object($parameters)) return $result;
            return parent::insert_object_into($parameters, Functions::class_name(__FILE__));
        }
        
        $name = trim($name);
        if(!$name) return 0;
        
        if($result = self::find($name)) return $result;
        $agent_id = parent::insert_fields_into(array('full_name' => $name, 'display_name' => $name, 'homepage' => $homepage, 'logo_url' => $logo_url), Functions::class_name(__FILE__));
        
        return $agent_id;
    }
    
    static function find($string)
    {
        return parent::find_by("full_name", $string, Functions::class_name(__FILE__));
    }
    
    static function find_by_mock_object($mock)
    {
        return parent::find_by_mock_obj($mock, Functions::class_name(__FILE__));
    }
}

?>