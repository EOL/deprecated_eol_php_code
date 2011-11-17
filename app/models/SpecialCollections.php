<?php
namespace php_active_record;

class SpecialCollection extends ActiveRecord
{
    public function focus()
    {
        return SpecialCollection::find_or_create_by_name('Focus');
    }
    
    public static function watch()
    {
        return SpecialCollection::find_or_create_by_name('Watch');
    }
}

?>