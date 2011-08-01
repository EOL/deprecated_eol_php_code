<?php
namespace php_active_record;

class RefIdentifier extends ActiveRecord
{
    public static $belongs_to = array(
            array('reference'),
            array('ref_identifier_type')
        );

}

?>