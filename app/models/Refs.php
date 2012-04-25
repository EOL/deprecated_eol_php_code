<?php
namespace php_active_record;

class Reference extends ActiveRecord
{
    static $table_name = 'refs';
    static $foreign_key = 'ref_id';
    
    public static $belongs_to = array(
            array('language')
        );
    
    public static $has_many = array(
            array('ref_identifiers')
        );
    
    public function add_ref_identifier($type_id, $value)
    {
        $value = $this->mysqli->escape($value);
        $this->mysqli->insert("INSERT IGNORE INTO ref_identifiers VALUES ($this->id, $type_id ,'$value')");
    }
    
    public function publish()
    {
        $this->published = 1;
        $this->visibility_id = Visibility::visible()->id;
        $this->save();
    }
}

?>