<?php
namespace eol_schema;

class ContentArchiveFieldValidationRule extends ContentArchiveValidationRule
{
    public function validate(&$field_value, $field_uri = NULL)
    {
        $success = call_user_func($this->validation_function, $field_value);
        if(!$success)
        {
            if($this->failure_type == 'error') $error = new ContentArchiveError();
            else $error = new ContentArchiveWarning();
            if($field_uri) $error->uri = $field_uri;
            elseif(is_array($this->field_uri))
            {
                $error->uri = $this->field_uri[0];
            }else $error->uri = $this->field_uri;
            $error->value = $field_value;
            $error->message = $this->failure_message;
            return $error;
        }
    }
}

?>