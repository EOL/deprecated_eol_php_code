<?php

// require_once("../../../FreshData/config/settingz.php");
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");
$params =& $_GET;
if(!$params) $params =& $_POST;
// echo "<pre>"; print_r($params); echo "</pre>";

$ctrler = new freshdata_controller($params);
$task = $ctrler->get_available_job("genHigherClass_job");
print_r($task);

//worked on script
//generate.php?file=$newfile&orig_file=$orig_file

$server_http_host = $_SERVER['HTTP_HOST'];
$server_script_name = $_SERVER['SCRIPT_NAME'];
$server_script_name = str_replace("form_result.php", "generate_jenkins.php", $server_script_name);

// exit("<pre><hr>$server_http_host<hr>$server_script_name</pre>");

$params['uuid'] = "eli173";

$cmd = PHP_PATH.' generate_jenkins.php ' . "'$newfile' '$orig_file' '$server_http_host' '$server_script_name'";
$cmd .= " 2>&1";
$ctrler->write_to_sh($params['uuid']."_inv", $cmd);

$cmd = $ctrler->generate_exec_command($params['uuid']."_inv"); //pass the desired basename of the .sh filename (e.g. xxx.sh then pass "xxx")
$c = $ctrler->build_curl_cmd_for_jenkins($cmd, $task);

/* to TSV destination here...
if(file_exists($params['destination'])) unlink($params['destination']);
*/

$shell_debug = shell_exec($c);
sleep(10);

echo "<pre><hr>cmd: $cmd<hr>c: $c<hr></pre>";
echo "<pre><hr>shell_debug: [$shell_debug]<hr></pre>";


// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid']."_inv");
if($ctrler->did_build_fail($build_status))
{
    $ctrler->display_message(array('type' => "error", 'msg' => "Build failed. Will need to investigate."));
}
elseif($ctrler->is_build_currently_running($build_status))
{
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing..."));
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Please check back later. Click <b>Refresh</b>."));
}
else
{
    if(file_exists($params['destination']) && filesize($params['destination'])) $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK. Click <b>Refresh</b>."));
    else                                                                        $ctrler->display_message(array('type' => "highlight", 'msg' => "Build is in unknown state. Will investigate"));
}
echo "<hr><pre>".$build_status."</pre><hr>";





?>