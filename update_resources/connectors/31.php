<?php
namespace php_active_record;
/*
31	Thursday 2018-04-05 02:14:58 AM	{"media_resource.tab":60942,"taxon.tab":11854} - eol-archive
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BiopixAPI');

$biopix_connector = new BiopixAPI;
$biopix_connector->get_all_taxa();

Functions::finalize_dwca_resource(31, false, true); //2nd param NOT a big file | 3rd param YES will delete working folders /31/
?>
