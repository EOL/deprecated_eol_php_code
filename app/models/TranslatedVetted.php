<?php
namespace php_active_record;

class TranslatedVetted extends ActiveRecord
{
    static $table_name = 'translated_vetted';
    
    public static $belongs_to = array(
            array('vetted', 'class_name' => 'vetted', 'foreign_key' => 'vetted_id'),
            array('language')
        );
}

?>