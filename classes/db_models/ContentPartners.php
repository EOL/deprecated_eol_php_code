<?php

class ContentPartner extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    static function find($agent_id)
    {
        return parent::find_by("agent_id", $agent_id, Functions::class_name(__FILE__));
    }
}

?>