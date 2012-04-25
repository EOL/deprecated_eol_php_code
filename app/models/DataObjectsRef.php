<?php
namespace php_active_record;

class DataObjectsRef extends ActiveRecord
{
    static $primary_key = null;
    
    public static $belongs_to = array(
            array('data_object'),
            array('reference')
        );
}

?>