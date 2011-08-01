<?php
namespace php_active_record;

class TranslatedStatus extends ActiveRecord
{
    public static $belongs_to = array(
            array('status'),
            array('language')
        );
}

?>