<?php

class NodeSynonym
{
    static $name;
    static $type;
    static $source;
    
    function __construct($name, $type, $source)
    {
        $this->name = $name;
        $this->type = $type;
        $this->source = $source;
    }
}

?>