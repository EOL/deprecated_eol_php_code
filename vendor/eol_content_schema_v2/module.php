<?php

require_once("DarwinCoreExtensionBase.php");
require_once("ContentArchiveErrorBase.php");
require_once("ContentArchiveValidationRule.php");
foreach(glob(__DIR__ . "/*.php") as $class) require_once($class);

?>