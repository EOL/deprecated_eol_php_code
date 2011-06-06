<?php
namespace php_active_record;

class HarvestEventsHierarchyEntry extends ActiveRecord
{
    public static $belongs_to = array(
            array('harvest_event'),
            array('hierarchy_entry'),
            array('status')
        );
}

?>