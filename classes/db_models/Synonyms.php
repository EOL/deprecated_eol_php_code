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
        return 0;
    }
}

?>