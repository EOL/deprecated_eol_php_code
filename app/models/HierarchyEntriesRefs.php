<?php
namespace php_active_record;

class HierarchyEntriesRef extends ActiveRecord
{
    static $primary_key = null;
    
    public static $belongs_to = array(
            array('hierarchy_entry'),
            array('reference')
        );
}

?>