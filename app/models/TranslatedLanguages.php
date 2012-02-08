<?php
namespace php_active_record;

class TranslatedLanguage extends ActiveRecord
{
    public static $belongs_to = array(
            array('original_language', 'class_name' => 'Language', 'foreign_key' => 'original_language_id'),
            array('language')
        );
}

?>