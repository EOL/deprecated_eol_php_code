<?php
namespace eol_schema;

class ContentArchiveValidationRule
{
    public $field_uri;
    public $validation_function;
    public $failure_type;
    public $failure_message;
    
    public function __construct($params = array())
    {
        $this->field_uri = @$params['field_uri'];
        $this->validation_function = @$params['validation_function'];
        $this->failure_type = @$params['failure_type'] ?: 'error'; # could also be 'warning'
        $this->failure_message = @$params['failure_message'] ?: "Failed $this->validation_function";
    }
}

?>