<?php

class Node
{
    static $id;
    static $name;
    static $name_id;
    static $rank;
    static $synonyms;
    static $children;
    static $parent;
    static $source;
    
    function __construct($name, $id)
    {
        $this->id = $id;
        $this->name = $name;
        $this->name_id = Name::insert($name);
        $this->synonyms = array();
        $this->children = array();
        $this->parent = false;
        $this->rank = false;
        $this->source = false;
    }
    
    function show($indent)
    {
        $string = Name::find_by_id($this->name_id)." (".$this->name_id.")";
        if($this->rank) $string .= " <small>(".$this->rank.")</small>";
        if($this->source) $string .= " <small>(".$this->source.")</small>";
        if($this->synonyms)
        {
            foreach($this->synonyms as $k => $v)
            {
                $string .= "<br>".str_repeat("&nbsp;", ($indent*8)+2)."<font color='gray'><small>".$v->name;
                if($v->type) $string .= " (".$v->type.")";
                if($v->source) $string .= " (".$v->source.")";
                $string .= "</small></font>";
            }
        }
        return $string;
    }
    
    function set_parent($node)
    {
        $this->parent = $node;
    }
    
    function add_synonym($name, $relation, $source)
    {
        $synonym = new NodeSynonym($name, $relation, $source);
        foreach($this->synonyms as $k => $v)
        {
            if($v->name==$synonym->name) return;
        }
        $this->synonyms[] = $synonym;
    }
    
    function add_child($node)
    {
        $this->children[$node->id] = $node;
    }
}

?>