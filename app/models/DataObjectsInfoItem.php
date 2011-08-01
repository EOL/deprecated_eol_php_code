<?php
namespace php_active_record;

class DataObjectsInfoItem extends ActiveRecord
{
    public static $belongs_to = array(
            array('data_object'),
            array('info_item')
        );
}

?>