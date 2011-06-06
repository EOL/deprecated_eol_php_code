<?php
namespace php_active_record;

class Agent extends ActiveRecord
{
    public function add_resouce($resource_id, $role_label)
    {
        if(!$resource_id) return 0;
        $resource_agent_role = ResourceAgentRole::find_or_create_by_label($role_label);
        $this->mysqli->insert("INSERT INTO agents_resources VALUES ($this->id, $resource_id, $resource_agent_role->id)");
    }
    
    public function update_cache_url($logo_cache_url)
    {
        $this->mysqli->update("UPDATE agents SET logo_cache_url=".$this->mysqli->escape($logo_cache_url)." WHERE id=$this->id");
    }
}

?>