<?php
namespace php_active_record;

class TranslatedLicense extends ActiveRecord
{
    public static $belongs_to = array(
            array('license'),
            array('language')
        );
}

?>