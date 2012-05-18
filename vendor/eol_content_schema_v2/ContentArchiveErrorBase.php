<?php
namespace eol_schema;

class ContentArchiveErrorBase
{
    public $file;
    public $line;
    public $uri;
    public $value;
    public $message;
    
    public function __construct($parameters = array())
    {
        $this->file = @$parameters["file"];
        $this->line = @$parameters["line"];
        $this->uri = @$parameters["uri"];
        $this->value = @$parameters["value"];
        $this->message = @$parameters["message"];
    }
    
    public function __toString()
    {
        $type = "Error";
        if(get_called_class() == __NAMESPACE__ . '\\ContentArchiveWarning') $type = "Warning";
        
        $string = "<b>$type</b>";
        if($this->file) $string .= " in file $this->file";
        if($this->line || $this->line === 0) $string .= " on line $this->line";
        if($this->uri) $string .= " field $this->uri";
        if($this->message) $string .= ": $this->message";
        if($this->value) $string .= " [value was \"$this->value\"]";
        return $string;
    }
}

?>