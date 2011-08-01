<?php
namespace php_active_record;

class TranslatedRank extends ActiveRecord
{
    public static $belongs_to = array(
            array('rank'),
            array('language')
        );
}

?>