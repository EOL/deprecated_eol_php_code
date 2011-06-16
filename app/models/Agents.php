<?php
namespace php_active_record;

class Agent extends ActiveRecord
{
    public function update_cache_url($logo_cache_url)
    {
        $this->mysqli->update("UPDATE agents SET logo_cache_url=".$this->mysqli->escape($logo_cache_url)." WHERE id=$this->id");
    }
}

?>