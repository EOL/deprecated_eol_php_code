<?php
namespace php_active_record;

class AudiencesDataObject extends ActiveRecord
{
    public static $belongs_to = array(
            array('data_object'),
            array('audience')
        );
}

?>