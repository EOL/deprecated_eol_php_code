<?php
namespace php_active_record;

class License extends ActiveRecord
{
    static function find_or_create_for_parser($string)
    {
        if($license = self::find_by_string($string)) return $license;
        return self::find_or_create_by_source_url($string);
    }
    
    static function find_by_string($string)
    {
        if($string == "public domain" && $license = self::find_by_title($string)) return $license;
        if($string == "not applicable" && $license = self::find_by_title($string)) return $license;
        if($string == "no known copyright restrictions" && $license = self::find_by_title($string)) return $license;
        return parent::find_by_source_url($string);
    }
}

?>