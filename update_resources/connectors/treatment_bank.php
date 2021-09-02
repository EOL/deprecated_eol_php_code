<?php
namespace php_active_record;
/* DATA-1896: TreatmentBank
as of Sep 1, 2021 = 611,618 items

php update_resources/connectors/treatment_bank.php _ '{"range_from": "1", "range_to":"100000"}'
php update_resources/connectors/treatment_bank.php _ '{"range_from": "100000", "range_to":"200000"}'
php update_resources/connectors/treatment_bank.php _ '{"task": "build_up_dwca_list"}'

php5.6 treatment_bank.php jenkins '{"range_from": "1", "range_to":"100000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "100000", "range_to":"200000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "200000", "range_to":"300000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "300000", "range_to":"400000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "400000", "range_to":"500000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "500000", "range_to":"612000"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TreatmentBankAPI');
// $GLOBALS["ENV_DEBUG"] = false;
$timestart = time_elapsed();

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;
/*Array(
    [range_from] => 1
    [range_to] => 100000
)*/
$from = @$param['range_from'];
$to = @$param['range_to'];
$task = @$param['task'];

if($from && $to) {
    $func = new TreatmentBankAPI();
    $func->start($from, $to); //initial operation - downloads all Plazi DwCA's locally
}
elseif($task == "build_up_dwca_list") {
    $resource_id = "TreatmentBank_4Pensoft";
    $func = new TreatmentBankAPI($resource_id);
    $func->build_up_dwca_list();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>