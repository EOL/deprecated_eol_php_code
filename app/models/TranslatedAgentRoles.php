<?php
namespace php_active_record;

class TranslatedAgentRole extends ActiveRecord
{
    public static $belongs_to = array(
            array('agent_role'),
            array('language')
        );
}

?>