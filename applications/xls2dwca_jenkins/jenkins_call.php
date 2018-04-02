<?php
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

$ctrler = new freshdata_controller(array());
$task = $ctrler->get_available_job("xls2dwca_job");
$postfix = "_xls2dwca";

$server_http_host = $_SERVER['HTTP_HOST'];
$server_script_name = $_SERVER['SCRIPT_NAME'];
$server_script_name = str_replace("form_result.php", "generate_jenkins.php", $server_script_name);

// print_r(pathinfo($newfile));
// exit("$newfile | $orig_file"); //e.g. temp/1509076643.txt | dwh_taxa.txt

$params['uuid'] = pathinfo($newfile, PATHINFO_FILENAME);

/* $params['destination'] = dirname(__FILE__) . "/temp/" . compute_destination($newfile, $orig_file); */
   $params['destination'] = "/html/eol_php_code/applications/xls2dwca_jenkins/temp/" . compute_destination($newfile, $orig_file); 
   //always use DOC_ROOT so u can switch from jenkins to cmdline. BUT DOC_ROOT won't work here either since /config/boot.php is not called here.


/* for more debugging...
echo "<br>newfile: [$newfile]";
echo "<br>orig_file: [$orig_file]";
echo "<br>destination: " . $params['destination']; 
echo "<br>uuid: " . $params['uuid']; 
echo "<br>server_http_host: [$server_http_host]";
echo "<br>server_script_name: [$server_script_name]";
echo "<hr>";
*/

/* $cmd = PHP_PATH.' generate_jenkins.php ' . "'$newfile' '$orig_file' '$server_http_host' '$server_script_name'"; */
$cmd = PHP_PATH.' spreadsheet_2_dwca.php jenkins _ ' . "'" . $params['destination'] . "'";

$cmd .= " 2>&1";
$ctrler->write_to_sh($params['uuid'].$postfix, $cmd);

$cmd = $ctrler->generate_exec_command($params['uuid'].$postfix); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
$c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);

/* to TSV destination here... not sure purpose of this one ???
if(file_exists($params['destination'])) unlink($params['destination']);
*/

$shell_debug = shell_exec($c);
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