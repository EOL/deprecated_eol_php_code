<?php
namespace php_active_record;
/* connector for all resources using the EOL spreadsheet template: 
https://github.com/eliagbayani/EOL-connector-data-files/raw/master/schema/eol_import_spreadsheet.xlsx (.xls also) 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/Spreadsheet2DwCA');
//===========================================================================================new - start -- handles cmdline params
// print_r($argv);
$cmdline_params['jenkins_or_cron']                  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']                      = @$argv[2]; //useful here
print_r($cmdline_params);
$resource_id = false;
if($val = $cmdline_params['resource_id'])
{
    $resource_id = $val;
    if    ($resource_id == 1007) $params['spreadsheet'] = "http://localhost/cp/spreadsheets/Arctic spreadsheets/alaskanarthropoda.xls.zip";
    elseif($resource_id == 727)  $params['spreadsheet'] = "http://opendata.eol.org/dataset/42fd51a0-e31a-4b2a-9f18-6e4f08242d42/resource/88e09288-0578-43b9-a618-b6e08f70fa47/download/usda-plants.xlsx.zip";
    else exit("\nProgram will terminate. Invalid resource_id [$resource_id].\n\n");
}
else //no resource_id
{
    $params['spreadsheet'] = "http://localhost/cp/spreadsheets/Arctic spreadsheets/test.xls";
    $resource_id = get_base_filename($params['spreadsheet']);
}
$params['resource_id'] = $resource_id;
//===========================================================================================new - end
$func = new Spreadsheet2DwCA($params);
$func->convert_spreadsheet_2_dwca();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";

function get_base_filename($file)
{
    $info = pathinfo($file);
    $arr = explode(".", $info['filename']);
    return $arr[0]."-adjusted";
}

?>