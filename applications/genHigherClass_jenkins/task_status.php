<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

// require_once("../../../FreshData/config/settingz.php");
require_once("../../../LiteratureEditor/Custom/lib/Functions.php");
require_once("../../../FreshData/controllers/other.php");
require_once("../../../FreshData/controllers/freshdata.php");

$params =& $_GET;
// if(!$params) $params =& $_POST;
$task = $params['task'];

$ctrler = new \freshdata_controller(array());

/*
// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid']."_getHC");
if($ctrler->did_build_fail($build_status)) {
    $ctrler->display_message(array('type' => "error", 'msg' => "Build failed. Will need to investigate."));
}
elseif($ctrler->is_build_currently_running($build_status)) {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing..."));
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Please check back later. You can use this <a href='task_status.php?task=$task&basename=$params[uuid]"."_getHC"."'>link to check status</a> anytime."));
    return;
}
else {
    if(file_exists($params['destination']) && filesize($params['destination'])) $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
    else                                                                        $ctrler->display_message(array('type' => "highlight", 'msg' => "Build is in unknown state. Will investigate"));
}
echo "<hr>Build status:<pre>".$build_status."</pre><hr>";
*/
require_once("show_build_status.php");


?>