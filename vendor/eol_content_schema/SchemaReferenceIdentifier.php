<?php

class SchemaReferenceIdentifier
{
    public $label;
    public $value;
    
    public function __construct($parameters)
    {
        $this->label = @$parameters["label"];
        $this->value = @$parameters["value"];
    }
}

?>