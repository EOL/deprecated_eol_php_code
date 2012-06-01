<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
// TODO - This should, of course, be required in a central lib:
require_once(DOC_ROOT . "vendor/php_resque/lib/Resque.php");
Resque::setBackend(RESQUE_SERVER);

class Reskewer
{
    
    public function perform()
    {
      // WORK HERE
      echo 'Working on: ';
      echo $this->args['foo'];
      echo "\n";
    }
    
}

// Here is the test, which, oddly, at the moment is created when the worker initializes. But hey! This IS a test.

$args = array('foo' => 'bar');
Resque::enqueue('notifications', 'Reskewer', $args);

?>
