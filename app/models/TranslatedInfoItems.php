<?php
namespace php_active_record;

class TranslatedInfoItem extends ActiveRecord
{
    public static $belongs_to = array(
            array('info_item'),
            array('language')
        );
}

?>