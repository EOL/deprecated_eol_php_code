<?php
namespace php_active_record;

class DataObjectsTableOfContent extends ActiveRecord
{
    public static $has_many = array(
            array('data_objects'),
            array('table_of_contents', 'foreign_key' => 'toc_id')
        );
}

?>