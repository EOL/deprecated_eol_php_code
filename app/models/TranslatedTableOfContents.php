<?php
namespace php_active_record;

class TranslatedTableOfContent extends ActiveRecord
{
    public static $belongs_to = array(
            array('table_of_content', array('foreign_key' => 'table_of_contents_id')),
            array('language')
        );
}

?>