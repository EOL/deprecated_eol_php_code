<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIConnector');

$resource_id = 999;
$ncbi = new CatalogueOfLifeConnector($resource_id); //non-existent library. This may not be used anymore...
$ncbi->build_archive();
Functions::set_resource_status_to_harvest_requested($resource_id);

?>