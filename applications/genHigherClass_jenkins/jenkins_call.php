<?php
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

$ctrler = new freshdata_controller(array());
$task = $ctrler->get_available_job("genHigherClass_job");

$server_http_host = $_SERVER['HTTP_HOST'];
$server_script_name = $_SERVER['SCRIPT_NAME'];
$server_script_name = str_replace("form_result.php", "generate_jenkins.php", $server_script_name);

// //temp/1509076643.txt | dwh_taxa.txt
// print_r(pathinfo($newfile));
// exit("$newfile | $orig_file");

$params['uuid'] = pathinfo($newfile, PATHINFO_FILENAME);
$params['destination'] = dirname(__FILE__) . "/temp/" . compute_destination($newfile, $orig_file);

// echo "<br>newfile: [$newfile]";
// echo "<br>orig_file: [$orig_file]";
// echo "<br>destination: " . $params['destination']; 

$cmd = PHP_PATH.' generate_jenkins.php ' . "'$newfile' '$orig_file' '$server_http_host' '$server_script_name'";
$cmd .= " 2>&1";
$ctrler->write_to_sh($params['uuid']."_getHC", $cmd);

$cmd = $ctrler->generate_exec_command($params['uuid']."_getHC"); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
$c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);

/* to TSV destination here...
if(file_exists($params['destination'])) unlink($params['destination']);
*/

$shell_debug = shell_exec($c);
// sleep(5);

// echo "<pre><hr>cmd: $cmd<hr>c: $c<hr></pre>";
// echo "<pre><hr>shell_debug: [$shell_debug]<hr></pre>";

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