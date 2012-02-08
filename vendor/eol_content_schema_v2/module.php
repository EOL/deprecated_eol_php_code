<?php

require_once("DarwinCoreExtensionBase.php");
require_once("ContentArchiveErrorBase.php");
foreach(glob(__DIR__ . "/*.php") as $class) require_once($class);

?>