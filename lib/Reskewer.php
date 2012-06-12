<?php
date_default_timezone_set('America/Denver');  // TODO - This should be moved to the config file, I think.
include_once(dirname(__FILE__) . "/../config/environment.php");

# Needed for work:
php_active_record\require_library("SplitConceptHandler");

class Reskewer
{

  public function perform()
  {
    echo "++ Reskewer Performing...\n";
    if ($this->args['cmd'] == 'split') {
      SplitConceptHandler::split_concept($this->args);
    } else {
      echo "++ No command available for " . $this->args['cmd'] . "\n\n";
    }
  }

}

?>
