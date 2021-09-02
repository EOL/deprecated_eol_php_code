<?php
namespace php_active_record;
/* DATA-1896: TreatmentBank
TreatmentBankAPI*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TreatmentBankAPI');
// $GLOBALS["ENV_DEBUG"] = false;
$timestart = time_elapsed();
$resource_id = "TreatmentBank";
$func = new TreatmentBankAPI($resource_id);
$func->start(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>