<?php
namespace php_active_record;

class InfoItem extends ActiveRecord
{
    public static $belongs_to = array(
            array('table_of_content', 'foreign_key' => 'toc_id')
        );
}

?>