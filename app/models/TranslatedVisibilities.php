<?php
namespace php_active_record;

class TranslatedVisibility extends ActiveRecord
{
    public static $belongs_to = array(
            array('visibility'),
            array('language')
        );
}

?>