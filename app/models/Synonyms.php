<?php

class Synonym extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        parent::initialize($param);
        if(@!$this->id) return;
    }
    
    function name()
    {
        if(@$this->name) return $this->name;
        
        $this->name = new Name($this->name_id);
        return $this->name;
    }
    
    function language()
    {
        if(@$this->language) return $this->language;
        
        $this->language = new Language($this->language_id);
        return $this->language;
    }
    
    function synonym_relation()
    {
        if(@$this->synonym_relation) return $this->synonym_relation;
        
        $this->synonym_relation = new SynonymRelation($this->synonym_relation_id);
        return $this->synonym_relation;
    }
    
    function agents()
    {
        $agents = array();
        
        $result = $this->mysqli->query("SELECT * FROM agents_synonyms WHERE synonym_id=".$this->id." ORDER BY view_order ASC");
        while($result && $row=$result->fetch_assoc())
        {
            $agents[] = new AgentSynonym($row);
        }
        
        return $agents;
    }
    
    public function add_agent($agent_id, $role, $view_order)
    {
        if(!$agent_id) return 0;
        $this->mysqli->insert("INSERT INTO agents_synonyms VALUES ($this->id, $agent_id, ".AgentRole::insert($role).", $view_order)");
    }
    
    static function insert($parameters)
    {
        if($result = self::find($parameters)) return $result;
        return parent::insert_fields_into($parameters, Functions::class_name(__FILE__));
    }
    
    static function find($parameters)
    {
        if(!$relation_id) $relation_id = 0;
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