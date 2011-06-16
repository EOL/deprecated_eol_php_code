<?php
namespace php_active_record;

class TranslatedMimeType extends ActiveRecord
{
    public static $belongs_to = array(
            array('mime_type'),
            array('language')
        );
}

?>