<?php
namespace php_active_record;

class Collection extends ActiveRecord
{
    public static $belongs_to = array(
            array('user'),
            array('peer_site')
        );

}

?>