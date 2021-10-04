<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'NorthAmericanFlora'; //DATA-1890
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("15423", "91155", "15427", "15428", "91144", "91225", "91362_resource");
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>