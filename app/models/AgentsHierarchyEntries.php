<?php
namespace php_active_record;

class AgentsHierarchyEntry extends ActiveRecord
{
    public static $belongs_to = array(
          array('agent'),
          array('agent_role'),
          array('hierarchy_entry')
        );
}

?>