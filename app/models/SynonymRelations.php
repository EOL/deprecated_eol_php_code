<?php
namespace php_active_record;

class SynonymRelation extends ActiveRecord
{
    public static function synonym()
    {
        return SynonymRelation::find_or_create_by_translated_label('synonym');
    }
    
    public static function common_name()
    {
        return SynonymRelation::find_or_create_by_translated_label('genbank common name');
    }
    
    public static function genbank_common_name()
    {
        return SynonymRelation::find_or_create_by_translated_label('common name');
    }
}

?>
