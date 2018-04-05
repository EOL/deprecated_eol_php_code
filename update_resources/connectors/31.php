<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BiopixAPI');

$biopix_connector = new BiopixAPI;
$biopix_connector->get_all_taxa();

Functions::finalize_dwca_resource(31, false, true); //2nd param NOT a big file | 3rd param YES will delete working folders /31/
?>
