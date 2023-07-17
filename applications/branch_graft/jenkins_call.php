<?php
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

$GLOBALS['ENV_DEBUG'] = true;
/* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
*/

$ctrler = new freshdata_controller(array());
$job_name = 'xls2dwca_job';
$task = $ctrler->get_available_job($job_name);
$postfix = "_xls2dwca";

/* for debugging:
$server_http_host = $_SERVER['HTTP_HOST'];
$server_script_name = $_SERVER['SCRIPT_NAME'];
$server_script_name = str_replace("form_result.php", "generate_jenkins.php", $server_script_name);
*/

$params['true_root'] = $true_DOC_ROOT;
$params['uuid'] = $time_var; //pathinfo($newfile, PATHINFO_FILENAME);

// echo "<pre>"; print_r($form); echo "</pre>"; exit("\neli 100\n");
/*Array(
    [form_url] => 
    [Filename_ID] => 123
)
Array(
    [form_url] => https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Trait_Data_Import/Trait_template.xlsx
    [Filename_ID] => 124
)*/

if(isset($form['Proj_refresh'])) $Proj_refresh = 1;
else                             $Proj_refresh = 0;
/* copied template
$json = '{"Proj":"'.$form['Proj'].'", "Dept":"'.$dept_map[$form['Dept']].'"
         , "Proj_refresh":"'.$Proj_refresh.'"
         , "timestart":"'.$timestart.'", "Lic_yr":"'.$form['Lic_yr'].'", "Lic_inst":"'.$form['Lic_inst'].'", "Lic_cont":"'.$form['Lic_cont'].'"}';
*/
$json = '{"Filename_ID":"'.$form['Filename_ID'].'","Short_Desc":"'.$form['Short_Desc'].'" , "timestart":"'.$timestart.'" , 
          "newfile_File_A":"'.$newfile_File_A.'" , "newfile_File_B":"'.$newfile_File_B.'" , 
          "fileA_taxonID":"'.$fileA_taxonID.'" , "fileB_taxonID":"'.$fileB_taxonID.'" , "uuid":"'.$time_var.'" ,
          "orig_file_A":"'.$orig_file_A.'" , "orig_file_B":"'.$orig_file_B.'" 
         }';

$params['json'] = $json;
// exit("\n$json\n");
// $params['destination'] = $for_DOC_ROOT . "/applications/specimen_image_export/" . $newfile; --- copied template
// $params['destination'] = $for_DOC_ROOT . "/applications/trait_data_import/" . $newfile;
// $params['destination'] = $for_DOC_ROOT . "/applications/taxonomic_validation/" . $newfile;
// $params['destination'] = $for_DOC_ROOT . "/applications/branch_graft/" . $newfile;  // not needed for branch graft
$params['destination'] = "";

//always use DOC_ROOT so u can switch from jenkins to cmdline. BUT DOC_ROOT won't work here either since /config/boot.php is not called here. So use $for_DOC_ROOT instead.
$params['Filename_ID'] = $form['Filename_ID'];
$params['Short_Desc'] = $form['Short_Desc'];

/* for more debugging...
echo "<br>newfile: [$newfile]";
echo "<br>orig_file: [$orig_file]";
echo "<br>destination: " . $params['destination']; 
echo "<br>uuid: " . $params['uuid']; 
echo "<br>server_http_host: [$server_http_host]";
echo "<br>server_script_name: [$server_script_name]";
echo "<hr>"; //exit;
*/

// php update_resources/connectors/marine_geo_image.php _ image_input.xlsx _ _ '$json'
// php update_resources/connectors/marine_geo_image.php _ _ 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/image_input.xlsx' uuid001 '$json'

/* for command-line development - working OK
php update_resources/connectors/trait_data_import.php _ Trait_template.xlsx _ _ '{"Filename_ID":"001", "timestart":"0.035333"}'
php update_resources/connectors/trait_data_import.php _ _ 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Trait_Data_Import/Trait_template.xlsx' uuid001 '{"Filename_ID":"001", "timestart":"0.035333"}'
*/

// $newfile = pathinfo($newfile, PATHINFO_BASENAME);
$newfile = "-nothing-";
/* copied template
if($form_url) $cmd = PHP_PATH.' taxonomic_validation.php jenkins _ ' . "'" . $form_url . "' ".$params['uuid']. " '".$params['json']."'"; //no filename but there is form_url and uuid
else          $cmd = PHP_PATH.' taxonomic_validation.php jenkins ' . "'" . $newfile . "' _ _ ". "'".$params['json']."'";
*/
/* since there is no $form_url, not being used for sometime now
if($form_url) $cmd = PHP_PATH.' branch_graft.php jenkins _ ' . "'" . $form_url . "' ".$params['uuid']. " '".$params['json']."'"; //no filename but there is form_url and uuid
else          $cmd = PHP_PATH.' branch_graft.php jenkins ' . "'".$params['json']."'";
*/
$cmd = PHP_PATH.' branch_graft.php jenkins ' . "'".$params['json']."'";

echo "<pre>";print_r($params);echo "</pre>"; //good debug
/*
command: [/opt/homebrew/opt/php@5.6/bin/php branch_graft.php jenkins '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.001884" , "newfile_File_A":"File_A_1688396971.tab" , "newfile_File_B":"File_B_1688396971.tsv" , "fileA_taxonID":"EOL-000000095511" , "fileB_taxonID":"EOL-000000095511" , "uuid":"1688396971" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"amoebozoatest.tsv" }']
command: [/opt/homebrew/opt/php@5.6/bin/php branch_graft.php jenkins '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.001568" , "newfile_File_A":"File_A_1688886859.tab" , "newfile_File_B":"File_B_1688886859.tsv" , "fileA_taxonID":"EOL-000002098554" , "fileB_taxonID":"MC" , "uuid":"1688886859" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"coltest.tsv.zip" }']
command: [/opt/homebrew/opt/php@5.6/bin/php branch_graft.php jenkins '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.002422" , "newfile_File_A":"File_A_1688918855.tab" , "newfile_File_B":"File_B_1688918855.tsv" , "fileA_taxonID":"EOL-000000105448" , "fileB_taxonID":"MG" , "uuid":"1688918855" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"coltest.tsv.zip" }']

Katja's 3 test runs:
1st: php update_resources/connectors/branch_graft.php _ '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.001884" , "newfile_File_A":"File_A_1688396971.tab" , "newfile_File_B":"File_B_1688396971.tsv" , "fileA_taxonID":"EOL-000000095511" , "fileB_taxonID":"EOL-000000095511" , "uuid":"1688396971" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"amoebozoatest.tsv" }'
2nd: php update_resources/connectors/branch_graft.php _ '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.001568" , "newfile_File_A":"File_A_1688886859.tab" , "newfile_File_B":"File_B_1688886859.tsv" , "fileA_taxonID":"EOL-000002098554" , "fileB_taxonID":"MC" , "uuid":"1688886859" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"coltest.tsv.zip" }'
3rd: php update_resources/connectors/branch_graft.php _ '{"Filename_ID":"","Short_Desc":"" , "timestart":"0.002422" , "newfile_File_A":"File_A_1688918855.tab" , "newfile_File_B":"File_B_1688918855.tsv" , "fileA_taxonID":"EOL-000000105448" , "fileB_taxonID":"MG" , "uuid":"1688918855" , "orig_file_A":"taxon.tab.zip" , "orig_file_B":"coltest.tsv.zip" }'

Array(
    [true_root] => /opt/homebrew/var/www/eol_php_code/
    [uuid] => 1688396971
    [json] => {"Filename_ID":"","Short_Desc":"" , "timestart":"0.002263" , 
          "newfile_File_A":"File_A_1688396971.tab" , "newfile_File_B":"File_B_1688396971.tsv" , 
          "fileA_taxonID":"EOL-000000095511" , "fileB_taxonID":"eli02" , "uuid":"1688396971"
         }
    [Filename_ID] => 
    [Short_Desc] => 
)*/
// exit("<br>command: [".$cmd."]<br>");

$cmd .= " 2>&1";
$ctrler->write_to_sh($params['uuid'].$postfix, $cmd);
$cmd = $ctrler->generate_exec_command($params['uuid'].$postfix); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
$c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);

$shell_debug = shell_exec($c); //normal operation
// echo "<pre>"; print_r($params['json']); echo "<hr>[$c]<hr></pre>"; exit;
// exit("\n-stop muna-\n");
// sleep(10);

/* for more debugging...
echo "<pre><hr>cmd: $cmd<hr>c: $c<hr></pre>";
echo "<pre><hr>shell_debug: [$shell_debug]<hr></pre>";
*/

require_once("show_build_status.php");

function compute_destination($newfile, $orig_file)
{
    $filename = pathinfo($newfile, PATHINFO_FILENAME);
    if(pathinfo($orig_file, PATHINFO_EXTENSION) == "zip") {
        $temp = pathinfo($orig_file, PATHINFO_FILENAME);
        $ext = pathinfo($temp, PATHINFO_EXTENSION);
    }
    else $ext = pathinfo($orig_file, PATHINFO_EXTENSION);
    $final = "$filename.$ext";
    return $final;
}
?>