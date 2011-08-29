<?php
namespace php_active_record;

class DataObjectsHierarchyEntry extends ActiveRecord
{
    public static $belongs_to = array(
            array('data_object'),
            array('hierarchy_entry')
        );
}

?>