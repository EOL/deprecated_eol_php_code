<?php
namespace eol_schema;

class ContentArchiveRowValidationRule extends ContentArchiveValidationRule
{
    public function validate($row_values)
    {
        $success = call_user_func($this->validation_function, $row_values);
        if(!$success)
        {
            if($this->failure_type == 'error') $error = new ContentArchiveError();
            else $error = new ContentArchiveWarning();
            $error->message = $this->failure_message;
            return $error;
        }
    }
}

?>