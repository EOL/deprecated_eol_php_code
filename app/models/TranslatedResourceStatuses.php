<?php
namespace php_active_record;

class TranslatedResourceStatus extends ActiveRecord
{
    public static $belongs_to = array(
            array('resource_status'),
            array('language')
        );
}

?>