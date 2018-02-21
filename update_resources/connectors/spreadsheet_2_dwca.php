<?php
namespace php_active_record;
/* connector for all resources using the EOL spreadsheet template: 
https://github.com/eliagbayani/EOL-connector-data-files/raw/master/schema/eol_import_spreadsheet.xlsx (.xls also) 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// exit("\nDown due to system maintenance.\n");
require_library('connectors/Spreadsheet2DwCA');
//===========================================================================================new - start -- handles cmdline params
// print_r($argv);
$cmdline_params['jenkins_or_cron']                  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']                      = @$argv[2]; //useful here
// print_r($cmdline_params);
$resource_id = false;
if($val = $cmdline_params['resource_id'])
{
    $resource_id = $val;
    if    ($resource_id == 727)  $params['spreadsheet'] = "http://opendata.eol.org/dataset/42fd51a0-e31a-4b2a-9f18-6e4f08242d42/resource/88e09288-0578-43b9-a618-b6e08f70fa47/download/usda-plants.xlsx.zip";
    elseif($resource_id == 991) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/7e44255d-8350-4229-aaad-ead941420ab2/download/arctic-amphibians-and-reptiles.xls";
    elseif($resource_id == 992) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/b4a34e11-3ff8-4207-8cea-523a35b1241b/download/arctic-birds.xls";
    elseif($resource_id == 993) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/86c775f0-2808-47cf-875a-f1a2b560299e/download/arctic-freshwater-fishes.xls";
    elseif($resource_id == 994) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/b8d8bf5b-c07b-4cda-95bd-b842a22d9938/download/arctic-lichens.xls";
    elseif($resource_id == 995) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/0c9edb69-b5e1-4db3-b2da-9135ea79913a/download/arctic-lichen-ecology.xls";
    elseif($resource_id == 996) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/8b015ce0-660b-4047-98dd-e30573f9c08e/download/arctic-liverworts.xls";
    elseif($resource_id == 997) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/d073d077-d154-4df7-b135-c0fb3e003d8b/download/arctic-mammals.xls";
    elseif($resource_id == 998) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/cd37772b-d082-4959-b715-3cafd607923c/download/arctic-marine-fishes.xls";
    elseif($resource_id == 999) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/e07e6031-d94a-4bdd-9ebf-344c963b3a59/download/arctic-protists.xls";
    elseif($resource_id == 1000) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/03115419-3f59-4717-9fb0-ce3981ffa2ea/download/arctic-register-of-marine-species.xls";
    elseif($resource_id == 1001) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/fdef0cc5-953a-42cd-a310-e4be79130c35/download/arctic-vascular-plants.xls";
    elseif($resource_id == 1002) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/36456461-e99c-4c2a-8c82-0cb6609e8aec/download/arctic-algae.xls";
    elseif($resource_id == 1005) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/df09a527-c826-43c3-a9f4-ba8414695db7/download/alaskanarthropoda.xls";
    elseif($resource_id == 1007) $params['spreadsheet'] = "http://opendata.eol.org/dataset/bd2b0922-950a-4e7b-bf21-fbc7f734a4d7/resource/aeb5f0a9-e506-4d9a-bec4-0b7f825902dd/download/arctic-arthropods-traits-1.xlsx"; //"http://localhost/cp/spreadsheets/Arctic spreadsheets/alaskanarthropoda.xls.zip";
    elseif($resource_id == 707) $params['spreadsheet'] = "http://opendata.eol.org/dataset/99da8344-22a4-4d9d-890d-44ac3fc34a33/resource/87a9bb7f-90f1-4cfd-a52c-09bb8f9c91f6/download/birds-animaldiversityweb.xlsx";
    elseif($resource_id == 'plant_forms_habitat_and_distribution') $params['spreadsheet'] = "https://opendata.eol.org/dataset/aab3818f-d538-4d5f-a3b8-b52d233c7ac0/resource/8e46a838-0394-42bd-9272-0a8591834713/download/plant-habit-habitat-and-distribution.xlsx";
    elseif($resource_id == 'world_wildlife_fund') $params['spreadsheet'] = "http://opendata.eol.org/dataset/8ace52ee-4c87-4231-8c38-7fa04b2efd37/resource/c2b5b9e3-0b87-498f-bf3a-84cbc937a28e/download/wff-regions-version-2.xlsx";
    elseif($resource_id == 'Shelled_animal_body_mass') $params['spreadsheet'] = "https://opendata.eol.org/dataset/d5242c81-873f-48a2-8e1e-824272869361/resource/21f1f27b-fa10-405a-81dc-00cb48700064/download/body-size-shells.xlsx";
    elseif($resource_id == 990) $params['spreadsheet'] = "https://opendata.eol.org/dataset/60b861a7-5530-4cb2-8737-b5a3340d0578/resource/5dd1890d-551a-452e-82de-ec36daea444a/download/chen-and-moles.xlsx";
    elseif($resource_id == 489) $params['spreadsheet'] = "https://opendata.eol.org/dataset/3851e293-6d35-453d-a54e-d6ecedb796b2/resource/2537dc6d-97b8-4b1d-a0ca-8e94ceb2450c/download/newispotsalinks.xlsx"; //iSpot links
    else exit("\nProgram will terminate. Invalid resource_id [$resource_id].\n\n");
}
else //no resource_id
{
    $params['spreadsheet'] = "http://localhost/cp/spreadsheets/Arctic spreadsheets/alaskanarthropoda.xls";
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