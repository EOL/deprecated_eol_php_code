<?php

define("USING_SPM", true);
include_once(dirname(__FILE__) . "/../../config/environment.php");


$url = @$_GET["url"];
$document = new RDFDocument($url);
$document->show();


?>
