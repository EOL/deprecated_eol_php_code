<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

require_library('ItalicsFixer');

$fixer = new ItalicsFixer;
$fixer->begin();

?>

