<?php
namespace php_active_record;

class User extends ActiveRecord
{
    public static $belongs_to = array(
            array('agent')
        );

}

?>