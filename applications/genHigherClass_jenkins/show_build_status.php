<?php
// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid']."_getHC");
if($ctrler->did_build_fail($build_status)) {
    $ctrler->display_message(array('type' => "error", 'msg' => "Build failed. Will need to investigate. Or you can try it again when system resources are free."));
}
elseif($ctrler->is_build_currently_running($build_status)) {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing..."));
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Please check back later. You can use this <a href='task_status.php?task=$task&uuid=$params[uuid]&destination=".urlencode($params['destination'])."'>link to check status</a> anytime."));
    return;
}
else {
    if(file_exists($params['destination']) && filesize($params['destination'])) $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
    else                                                                        $ctrler->display_message(array('type' => "highlight", 'msg' => "Build is in unknown state. Will investigate. Or you can try it again when system resources are free."));
}
echo "<hr>Build status:<pre>".$build_status."</pre><hr>";
?>
