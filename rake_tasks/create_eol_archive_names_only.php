<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli =& $GLOBALS['db_connection'];

require_library('EOLArchiveNames');
$archive_creator = new EOLArchiveNames();
$archive_creator->create();

?>
