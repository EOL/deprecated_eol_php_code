<?php
namespace php_active_record;

class TranslatedAudience extends ActiveRecord
{
    public static $belongs_to = array(
            array('audience'),
            array('language')
        );
}

?>