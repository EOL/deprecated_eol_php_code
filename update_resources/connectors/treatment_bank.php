<?php
namespace php_active_record;
/* DATA-1896: TreatmentBank
as of Sep 1, 2021 = 611,618 items
STEP 1:
php update_resources/connectors/treatment_bank.php _ '{"range_from": "1", "range_to":"100000"}'
php update_resources/connectors/treatment_bank.php _ '{"range_from": "100000", "range_to":"200000"}'
{total of 6 ranges, see jenkins}

6 ranges in jenkins:
php5.6 treatment_bank.php jenkins '{"range_from": "1", "range_to":"100000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "100000", "range_to":"200000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "200000", "range_to":"300000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "300000", "range_to":"400000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "400000", "range_to":"500000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "500000", "range_to":"612000"}'

STEP 2:
php update_resources/connectors/treatment_bank.php _ '{"task": "build_up_dwca_list"}'
-> generates /resources/reports/Plazi_DwCA_list.txt

STEP 3:
php update_resources/connectors/treatment_bank.php _ '{"task": "generate_single_dwca"}'
-> generates TreatmentBank.tar.gz

STEP 4:
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"TreatmentBank", "subjects":"Uses"}'
-> generates TreatmentBank_ENV.tar.gz
-> last step
================== STATS ==================
TreatmentBank	    Wed 2021-09-08 04:23:09 PM	{                                                   "media.tab":596261, "taxon.tab":597054, "time_elapsed":{"sec":66673.13, "min":1111.22, "hr":18.52}}
TreatmentBank_ENV	Tue 2021-09-28 02:37:49 PM	{"MoF.tab":1554957, "occur_specific.tab":1554957,                       "taxon.tab":597054, "time_elapsed":{"sec":317931.33, "min":5298.86, "hr":88.31, "day":3.68}}
TreatmentBank	    Tue 2021-10-05 04:07:26 AM	{                                                   "media.tab":596261, "taxon.tab":597054, "time_elapsed":{"sec":66569.05, "min":1109.48, "hr":18.49}}
TreatmentBank_ENV	Tue 2021-10-05 05:38:14 AM	{"MoF.tab":1554958, "occur_specific.tab":1554958,                       "taxon.tab":597054, "time_elapsed":{"sec":5437.94, "min":90.63, "hr":1.51}}
TreatmentBank_ENV	Thu 2021-10-28 03:16:53 AM	{"MoF.tab":1554958, "occur_specific.tab":1554958,                       "taxon.tab":597054, "time_elapsed":{"sec":12692.65, "min":211.54, "hr":3.53}}
after filter using EOL terms file:
TreatmentBank	    Thu 2022-01-27 03:02:02 AM	{                                                   "media.tab":596261, "taxon.tab":597054, "time_elapsed":{"sec":67599.45, "min":1126.66, "hr":18.78}}
TreatmentBank_ENV	Thu 2022-01-27 06:50:50 AM	{"MoF.tab":1818376, "occur_specific.tab":1818376,                       "taxon.tab":597054, "time_elapsed":{"sec":13718.37, "min":228.64, "hr":3.81}}
after cleaning scientificName e.g. https://zenodo.org/record/5688763#.YflRD_XMJ_Q
TreatmentBank	    Fri 2022-02-04 03:50:57 AM	{                                                   "media.tab":596261, "taxon.tab":597053, "time_elapsed":{"sec":66563.64, "min":1109.39, "hr":18.49}}
TreatmentBank_ENV	Fri 2022-02-04 05:42:20 AM	{"MoF.tab":1818376, "occur_specific.tab":1818376,                       "taxon.tab":597053, "time_elapsed":{"sec":6675.69, "min":111.26, "hr":1.85}}
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
    $func = new TreatmentBankAPI();
    $func->build_up_dwca_list();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
elseif($task == "generate_single_dwca") {
    require_library('connectors/DwCA_Aggregator_Functions');
    require_library('connectors/DwCA_Aggregator');
    $resource_id = "TreatmentBank";
    $func = new DwCA_Aggregator($resource_id, false, 'regular');
    $func->combine_Plazi_Treatment_DwCAs();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>