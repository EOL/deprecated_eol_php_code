<?php

//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
//define('ENVIRONMENT', 'integration');
include_once(dirname(__FILE__) . "/../config/start.php");

Functions::log("Starting top_images");

$top_images = new TopImages();
$top_images->begin_process();
$top_images->top_concept_images(true);
$top_images->top_concept_images(false);

Functions::log("Ended top_images");

?>