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

$params['uuid'] = "eli173";

$cmd = PHP_PATH.' generate_jenkins.php ' . "'$newfile' '$orig_file'";
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



?>