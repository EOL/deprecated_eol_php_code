<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator');
$resource_id = 'MoftheAES_resources';
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("91362", "91362_species");
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>