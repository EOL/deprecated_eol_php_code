<?php
namespace php_active_record;

class AgentsDataObject extends ActiveRecord
{
    public static $belongs_to = array(
          array('agent'),
          array('agent_role'),
          array('data_object')
        );
}

?>