<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
include_once(dirname(__FILE__) . "/../lib/Reskewer.php");
// TODO - This should, of course, be required in a central lib:
require_once(DOC_ROOT . "vendor/php_resque/lib/Resque.php");
Resque::setBackend(RESQUE_SERVER);

$first_arg = @$argv[1];

$args = array('foo' => $first_arg);
Resque::enqueue('php', 'Reskewer', $args);

?>
