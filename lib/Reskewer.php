<?php
date_default_timezone_set('America/Denver');

include_once(dirname(__FILE__) . "/../config/environment.php");

class Reskewer
{
    
    public function perform()
    {
      // WORK HERE
      echo "\n\nWorking on: ";
      echo $this->args['foo'];
      echo "\n\n";
    }
    
}

?>
