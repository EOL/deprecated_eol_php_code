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
    // 990  Friday 2018-03-09 12:02:16 AM   {"agents.txt":0,"associations.txt":17600,"common names.txt":0,"events.txt":0,"measurements or facts.txt":24369,"media.txt":0,"occurrences.txt":3721,"references.txt":0,"taxa.txt":3721}
    elseif($resource_id == 495) $params['spreadsheet'] = "https://opendata.eol.org/dataset/3851e293-6d35-453d-a54e-d6ecedb796b2/resource/2537dc6d-97b8-4b1d-a0ca-8e94ceb2450c/download/newispotsalinks.xlsx"; //iSpot links
    elseif($resource_id == 1014) $params['spreadsheet'] = "https://opendata.eol.org/dataset/89f382c4-53e1-4df1-9b7c-0f97bc30ded2/resource/530c1eaa-31d7-4c7c-b3bd-eb4d4ab924b0/download/life-history-data-of-lizards-of-the-world-export.xlsx"; //Life history data of lizards of the world
    elseif($resource_id == 1017) $params['spreadsheet'] = "http://opendata.eol.org/dataset/ba96c75b-0864-42d9-9c05-89db2dec2a91/resource/ccb99dc8-0a89-4c49-83c2-8a12ac3c55d9/download/male-tenure-length-and-variance-in-lifetime-reproductive-success-recorded-for-mammals-transfer.xls"; //Male tenure length
    elseif($resource_id == 1024) $params['spreadsheet'] = "https://opendata.eol.org/dataset/0266ba72-d6cb-428e-adb0-aa8c176973ce/resource/552522eb-9b98-40d9-b992-2c4e098abe79/download/egg-characteristics-and-breeding-season-for-woods-hole-species2.xlsx"; //Woods Hole Species
    elseif($resource_id == 1019) $params['spreadsheet'] = "http://opendata.eol.org/dataset/8fea7f08-d4c3-42c5-bcf3-402364c8ca7f/resource/f3deb884-0fe7-4588-8d46-721d3e1838c8/download/avian-mass-export.xlsx"; //Avian Mass Data
    elseif($resource_id == 1013) $params['spreadsheet'] = "http://opendata.eol.org/dataset/8fea7f08-d4c3-42c5-bcf3-402364c8ca7f/resource/e31881d5-c270-4b8a-ba9d-51a6a1ab0a7d/download/pterosaurdata-transfer.xlsx"; //Pterosaur Data
    elseif($resource_id == 986) $params['spreadsheet'] = "http://opendata.eol.org/dataset/68dbf477-b1ab-4a65-ada7-615470dd4c84/resource/9be267ec-cd28-458c-aae3-e834d2f1f20b/download/bird-incubation.xlsx"; //Bird incubation
    elseif($resource_id == 1009) $params['spreadsheet'] = "http://opendata.eol.org/dataset/dab92513-fccc-4418-bd8d-70095654edf1/resource/d2d12cf6-378d-4af0-8719-0bdaada455ec/download/carnivore-dinosaurs.xlsx"; //Predatory Dinosaurs of the World
    elseif($resource_id == 1010) $params['spreadsheet'] = "http://opendata.eol.org/dataset/442b9b6f-e5d5-4972-a8e6-a65cdbe7721b/resource/3fde5a51-3000-482e-95ab-55e16bfd28cc/download/parrot-fish.xlsx"; //Evolutionary History Of The Parrotfishes
    elseif($resource_id == 1012) $params['spreadsheet'] = "http://opendata.eol.org/dataset/e33a9544-1aa1-4e50-9efa-c04ef4098d57/resource/678d8a40-3375-4ad1-b8d5-eedb12619936/download/dana-dinosaur-transfer.xlsx"; //Dinosaur Papers
    elseif($resource_id == 1016) $params['spreadsheet'] = "http://opendata.eol.org/dataset/8fea7f08-d4c3-42c5-bcf3-402364c8ca7f/resource/3b63d19a-1689-4767-8b75-551ec4ee2ae2/download/reptile-export.xlsx"; //Body Size datasets
    elseif($resource_id == 1018) $params['spreadsheet'] = "http://opendata.eol.org/dataset/4420d805-45e1-4e28-b7fd-2b563abbb3ea/resource/7479d3d0-e329-41ba-bf34-7e1177572ab8/download/social-systems-of-mammalian-species-transfer.xlsx"; //Social systems of mammals
    elseif($resource_id == 1020) $params['spreadsheet'] = "http://opendata.eol.org/dataset/763de247-67c1-4656-97b1-19919dba1334/resource/bd9b9a3e-c7ae-4d55-920a-a5f184726ea5/download/life-history-characteristics-of-placental-non-volant-mammals.xlsx"; //Life History Characteristics of Placental Non-Volant Mammals
    elseif($resource_id == 1021) $params['spreadsheet'] = "http://opendata.eol.org/dataset/f14533f7-793a-416d-952d-f73fb02413e8/resource/7273e829-7dd7-4b9e-a4ea-296a373f8bfa/download/macroecological-mammalian-body-mass-copy.xlsx"; //Macroecological database of mammalian body mass
    elseif($resource_id == 1022) $params['spreadsheet'] = "http://opendata.eol.org/dataset/b417c9b9-5246-4a9a-9f9d-39aae9954303/resource/0b223325-19ea-427a-9499-fa9c254f39f6/download/mikesell-phenological-data.xlsx"; //Mikesell phenological data from Wauseon, Ohio, USA, 1883-1912
    elseif($resource_id == 1023) $params['spreadsheet'] = "http://opendata.eol.org/dataset/d339b8df-a57f-4612-9dc5-c7963b50f4f4/resource/71d952ec-af42-40b2-b1a8-b4f8d56f6ad6/download/eastern-export.xlsx"; //Eastern US old fields plant traits
    elseif($resource_id == 985) $params['spreadsheet'] = "http://opendata.eol.org/dataset/f49ee7e9-65d4-4d00-af86-29ca98f83739/resource/c36f8fdb-750b-4301-ac3b-30c2e93a90b2/download/toxic-set-done.xlsx"; //Toxic
    //----------------------- start new below
    elseif($resource_id == "x") $params['spreadsheet'] = "http://localhost/tests/aBrose%20et%20al.xlsx"; //Brose et al -- undefined columns, not working
    
    // template
    // elseif($resource_id == res_id) $params['spreadsheet'] = "https_xls_path"; //res_name
    
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