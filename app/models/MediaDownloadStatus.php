<?php
namespace php_active_record;

class MediaDownloadStatus extends ActiveRecord
{
    public static $belongs_to = array(
            array('peer_site')
        );
    
}

?>