<?php
namespace php_active_record;

class ContentPartner extends ActiveRecord
{
    public static $belongs_to = array(
            array('user')
        );

}

?>