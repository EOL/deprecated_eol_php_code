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
}

?>