<?php

class AgentSynonym extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->synonym_id) return;
        
        $this->agent = new Agent($this->agent_id);
        $this->agent_role = new AgentRole($this->agent_role_id);
    }
}

?>