<?php
namespace php_active_record;

class Synonym extends ActiveRecord
{
    public static $belongs_to = array(
            array('name'),
            array('language'),
            array('synonym_relation')
        );
    
    public static $has_many = array(
            array('agents_synonyms'),
            array('agents', 'through' => 'agents_synonyms')
        );
    
    static function find_by_array($parameters)
    {
        if(@!$parameters['synonym_relation_id']) $parameters['synonym_relation_id'] = 0;
        if(@!$parameters['language_id']) $parameters['language_id'] = 0;
        if(@!$parameters['hierarchy_entry_id']) $parameters['hierarchy_entry_id'] = 0;
        if(@!$parameters['hierarchy_id']) $parameters['hierarchy_id'] = 0;
        $result = $GLOBALS['db_connection']->query("SELECT SQL_NO_CACHE id
            FROM synonyms
            WHERE name_id=". $parameters['name_id'] ."
            AND synonym_relation_id=". $parameters['synonym_relation_id'] ."
            AND language_id=". $parameters['language_id'] ."
            AND hierarchy_entry_id=". $parameters['hierarchy_entry_id'] ."
            AND hierarchy_id=". $parameters['hierarchy_id']);
        if($result && $row=$result->fetch_assoc()) return $row['id'];
        return false;
    }
}

?>