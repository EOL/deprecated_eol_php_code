<?php
namespace php_active_record;

class TranslatedServiceType extends ActiveRecord
{
    public static $belongs_to = array(
            array('service_type'),
            array('language')
        );
}

?>