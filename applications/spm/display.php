<?php

define("USING_SPM", true);
define("DEBUG", true);
include_once("../../config/start.php");


$url = @$_GET["url"];
$document = new RDFDocument($url);
$document->show();


?>
