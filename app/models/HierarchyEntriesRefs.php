<?php
namespace php_active_record;

class HierarchyEntriesRef extends ActiveRecord
{
    public static $belongs_to = array(
            array('hierarchy_entry'),
            array('reference')
        );
}

?>