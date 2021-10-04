<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'NorthAmericanFlora_Plants'; //DATA-1890
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("15422", "15426", "15424", "15425", "15429", "15430", "91357", "91336", "91365", "91361", "91337", "91208", "91339", "91344", 
"91343", "91461", "91297", "91334", "91228", "15432", "15441", "91534", "15434", "15435", "15436", "15437", "15438", "91335", "90479", "91535", 
"91287", "91538", "15439", "15440", "91527", "91338", "91340", "91342", "91116", "91346", "91348", "91345", "91209", "91529");
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>