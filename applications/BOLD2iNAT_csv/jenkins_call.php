<?php
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

/* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true when debugging
*/

$ctrler = new freshdata_controller(array());
$job_name = 'xls2dwca_job';
$task = $ctrler->get_available_job($job_name);
$postfix = "_xls2dwca";

// /* for debugging:
$server_http_host = $_SERVER['HTTP_HOST'];
$server_script_name = $_SERVER['SCRIPT_NAME'];
$server_script_name = str_replace("form_result.php", "generate_jenkins.php", $server_script_name);
// */

$params['true_root'] = $true_DOC_ROOT;
$params['uuid'] = pathinfo($newfile, PATHINFO_FILENAME);

/* IMPORTANT: un-comment during development
echo "<pre>"; print_r($form); echo "</pre>";
*/

/*Array(
    [form_url] => 
    [Proj] => KANB
    [Dept] => fishes
    [Lic] => CreativeCommons – Attribution Non-Commercial Share-alike (by-nc-sa)
    [Lic_yr] => my_year
    [Lic_inst] => my_inst
    [Lic_cont] => my_contact
)*/
// /* copied template
$dept_map['fishes'] = 'FISH';
$dept_map['mammals'] = 'MAMMALS';
$dept_map['herps'] = 'HERPS'; //'AMPHIBIANS & REPTILES';
$dept_map['birds'] = 'BIRDS';
$dept_map['botany'] = 'BOTANY';
$dept_map['paleo'] = 'PALEOBIOLOGY';
// */
if(isset($form['Proj_refresh'])) $Proj_refresh = 1;
else                             $Proj_refresh = 0;

$json = '{"JWT":"'.$form['JWT'].'" , "token_type":"'.$form['token_type'].'", "Proj":"'.@$form['Proj'].'", "Taxon":"'.@$form['Taxon'].'"
        , "Dept":"'.$dept_map[@$form['Dept']].'", "Proj_refresh":"'.$Proj_refresh.'"
        , "Katie_O_csv":"Yes"}';
        // , "Lic":"'.@$form['Lic'].'", "Lic_yr":"'.@$form['Lic_yr'].'", "Lic_inst":"'.@$form['Lic_inst'].'", "Lic_cont":"'.@$form['Lic_cont'].'"}';
$params['json'] = $json;
// $params['Proj_and_Taxon'] = $form['Proj']."_".str_replace(' ','_',$form['Taxon']);
$params['Proj_and_Taxon'] = 'KatieO_summary';

   $params['destination'] = $for_DOC_ROOT . "/applications/specimen_image_export/" . $newfile;
   //always use DOC_ROOT so u can switch from jenkins to cmdline. BUT DOC_ROOT won't work here either since /config/boot.php is not called here. So use $for_DOC_ROOT instead.

/* for more debugging...
echo "<br>newfile: [$newfile]";
echo "<br>orig_file: [$orig_file]";
echo "<br>destination: " . $params['destination']; 
echo "<br>uuid: " . $params['uuid']; 
echo "<br>server_http_host: [$server_http_host]";
echo "<br>server_script_name: [$server_script_name]";
echo "<br>json: [$json]";
echo "<hr>"; //exit("\nddd\n");
*/

// php update_resources/connectors/marine_geo_image.php _ image_input.xlsx _ _ '$json'
// php update_resources/connectors/marine_geo_image.php _ _ 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MarineGEO/image_input.xlsx' uuid001 '$json'

$newfile = pathinfo($newfile, PATHINFO_BASENAME);
// /* eol-archive
if($form_url) $cmd = PHP_PATH.' bold2inat.php jenkins _ ' . "'" . $form_url . "' ".$params['uuid']. " '".$params['json']."'"; //no filename but there is form_url and uuid
else          $cmd = PHP_PATH.' bold2inat.php jenkins ' . "'" . $newfile . "' _ _ ". "'".$params['json']."'";
// */
/* Mac mini - during development using local Jenkins
if($form_url) $cmd = PHP_PATH.' bold2inat.php _ _ ' . "'" . $form_url . "' ".$params['uuid']. " '".$params['json']."'"; //no filename but there is form_url and uuid
else          $cmd = PHP_PATH.' bold2inat.php _ ' . "'" . $newfile . "' _ _ ". "'".$params['json']."'";
*/

// exit("\n[$cmd]\nbbb\n"); //good debug to stop operation
/*
[/usr/local/php5/bin/php bold2inat.php jenkins '1614743288.csv' _ _ '{"JWT":"5e18007deaf128b0f2f87d8aea79da3aae7d0b127526ba806761cb0ef0100050" , "token_type":"Bearer", "Proj":"", "Taxon":"", "Dept":"FISH" , "Proj_refresh":"0" , "Katie_O_csv":"Yes"}']
[/usr/local/php5/bin/php bold2inat.php _ '1614873457.csv' _ _ '{"JWT":"580bed96252d1f622414a1a24321542be89b20d3c0b9dcac42f915534123608c" , "token_type":"Bearer", "Proj":"", "Taxon":"" , "Dept":"FISH" , "Proj_refresh":"0" , "Katie_O_csv":"Yes"}'
*/

// /* un-comment in real operation
$cmd .= " 2>&1";
$ctrler->write_to_sh($params['uuid'].$postfix, $cmd);
$cmd = $ctrler->generate_exec_command($params['uuid'].$postfix); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
$c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);
// */

/* to TSV destination here... not sure purpose of this one ???
if(file_exists($params['destination'])) unlink($params['destination']);
*/

$shell_debug = shell_exec($c);
// sleep(10);

/* for more debugging...
echo "<pre><hr>cmd: $cmd<hr>c: $c<hr></pre>";
echo "<pre><hr>shell_debug: [$shell_debug]<hr></pre>";
*/

// /* un-comment in real operation
require_once("show_build_status.php");
// */

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