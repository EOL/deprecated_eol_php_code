<?php

class SchemaReferenceIdentifier
{
    static $label;
    static $value;
    
    public function __construct($parameters)
    {
        $this->label = @$parameters["label"];
        $this->value = @$parameters["value"];
    }
}

?>