<?php
namespace php_active_record;

class AgentsSynonym extends ActiveRecord
{
    public static $belongs_to = array(
          array('agent'),
          array('agent_role')
        );
}

?>