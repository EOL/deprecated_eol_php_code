<?php
namespace php_active_record;

class MimeType extends ActiveRecord
{
    public static function flash()
    {
        return MimeType::find_or_create_by_translated_label('video/x-flv');
    }
}

?>