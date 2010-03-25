<?php

include_once(dirname(__FILE__) . "/../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;
require_library('SiteStatistics');

Functions::log("Starting site statistics");

$stats = new SiteStatistics();
$stats->insert_taxa_stats();
$stats->insert_data_object_stats();

Functions::log("Ended site statistics");


?>