<?php
namespace php_active_record;

class Language extends ActiveRecord
{
    public static function find_or_create_for_parser($string)
    {
        $string = trim($string);
        if(!$string) return null;
        if($cache = Cache::get('language_insert_'.$string)) return $cache;
        
        if($result = self::find_by_iso_639_1($string)) $language = $result;
        elseif($result = self::find_by_iso_639_2($string)) $language = $result;
        elseif($result = self::find_by_iso_639_3($string)) $language = $result;
        else
        {
            $language = self::find_or_create_by_label($string);
        }
        
        if(cache_model(self::table_name())) Cache::set('language_insert_'.$string, $language);
        return $language;
    }
    
    static function unknown_ids()
    {
        $return = array();
        
        $return[] = Language::find_or_create_by_label("Undetermined")->id;
        $return[] = Language::find_or_create_by_label("Unknown")->id;
        $return[] = Language::find_or_create_by_label("unspecified")->id;
        $return[] = Language::find_or_create_by_label("Miscellaneous languages")->id;
        $return[] = Language::find_or_create_by_label("Multiple languages")->id;
        
        return $return;
    }
}

?>