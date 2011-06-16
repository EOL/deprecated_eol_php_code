<?php
namespace php_active_record;

class TranslatedSynonymRelation extends ActiveRecord
{
    public static $belongs_to = array(
            array('synonym_relation'),
            array('language')
        );
}

?>