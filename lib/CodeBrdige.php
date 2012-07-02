<?php
date_default_timezone_set('America/Denver');  // TODO - This should be moved to the config file, I think.
include_once(dirname(__FILE__) . "/../config/environment.php");

# Needed for work:
php_active_record\require_library("SplitConceptHandler");
php_active_record\require_library("MoveConceptHandler");
php_active_record\require_library("MergeConceptHandler");

// This is a way for PHP and Ruby to talk across Resque. If the class names are (exactly) the same, they can pass
// JSON back and forth fairly simply.
class CodeBridge
{

  public function perform()
  {
    if ($this->args['cmd'] == 'split') {
      SplitConceptHandler::split_concept($this->args);
    } elseif ($this->args['cmd'] == 'move') {
      MoveConceptHandler::split_concept($this->args);
    } elseif ($this->args['cmd'] == 'merge') {
      MergeConceptHandler::split_concept($this->args);
    } else {
      throw new Exception("No command available for " . $this->args['cmd']);
    }
  }

}

?>
