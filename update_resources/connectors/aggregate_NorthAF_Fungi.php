<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'NorthAmericanFlora_Fungi'; //DATA-1890
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("15404", "15405", "15406", "15407", "15408", "15409", "15410", "15411", "15412", "15413", "15414", "15415", "15416", "15417", "15418", "15419", "15420", "15421");
/* during dev only - association files
$resource_ids = array("15406");
*/
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>