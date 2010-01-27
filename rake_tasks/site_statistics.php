<?php

include_once(dirname(__FILE__) . "/../config/start.php");

Functions::log("Starting site statistics");

$stats = new SiteStatistics();
$stats->insert_taxa_stats();
$stats->insert_data_object_stats();

Functions::log("Ended site statistics");


?>