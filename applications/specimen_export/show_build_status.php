<?php
$job_name = 'xls2dwca_job';
$postfix = "_xls2dwca";
$str = "Copy 'Build status' and send to <i>eagbayani@eol.org</i>. &nbsp; Or you can try it again when system resources are free. &nbsp; <a href='index.php'>&lt;&lt; Back to main</a>";
// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid'].$postfix);
if($ctrler->did_build_fail($build_status)) {
    $ctrler->display_message(array('type' => "error", 'msg' => "Process failed. &nbsp; $str"));
}
elseif($ctrler->is_build_currently_running($build_status)) {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing... Page will refresh every 5 seconds."));
    $path = "task_status.php?task=$task&uuid=$params[uuid]&destination=".urlencode($params['destination']);
    $ctrler->display_message(array('type' => "highlight", 'msg' => "OR you can check back later. &nbsp; You can use this <a href='$path'>link to check status</a> anytime."));

    $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $actual_link = str_ireplace("form_result.php", $path, $actual_link);
    echo "<meta http-equiv='refresh' content='5;url=".$actual_link."'>";
    // return;
}
else {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Seems done..."));
    // print_r($params);
    /* Array ( [task] => xls2dwca_job_1 
               [uuid] => 1574931187 
               [destination] => /Library/WebServer/Documents/eol_php_code//applications/specimen_export/temp/1574931187.xlsx )
    */
    /* Delete temp files */
    $dirname = pathinfo($params['destination'], PATHINFO_DIRNAME).'/';
    $extensions = array('.xlsx', '.xls', '.zip'); //possible extensions
    foreach($extensions as $ext) {
        $tmp = $dirname.$params['uuid'].$ext;
        if(file_exists($tmp)) unlink($tmp);
    }
    
    /*
    $from                   = DOC_ROOT . "applications/content_server/resources/" . $params['uuid'] . ".tar.gz";
    $final_archive_gzip_url = DOC_ROOT . "applications/content_server/resources/xls2dwca/" . $params['uuid'] . ".tar.gz";
    if(file_exists($from)) {
        $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
        $to_delete = DOC_ROOT . "applications/xls2dwca_jenkins/temp/" . pathinfo($params['destination'], PATHINFO_BASENAME); //to delete the temporary spreadsheet (.xlsx .xls)
        if(unlink($to_delete)) {} //$ctrler->display_message(array('type' => "highlight", 'msg' => "Temp file deleted: [$to_delete]"));             //for debug
        else                   $ctrler->display_message(array('type' => "highlight", 'msg' => "ERROR: Temp file NOT deleted: [$to_delete]"));
        // print_r($params);
        Functions::file_rename($from, $final_archive_gzip_url);
        if($final_archive_gzip_url) {
            $final_archive_gzip_url = str_replace(DOC_ROOT, WEB_ROOT, $final_archive_gzip_url);
            echo "=======================================================<br>";
            echo "The archive is now available at <a href='$final_archive_gzip_url'>$final_archive_gzip_url</a><br><br>
                You can save this file to your computer.<br><br>
                This file will be stored on our server for a week, after which it will be removed.<br><br>
                You can also use this URL in our 
                <a href='../dwc_validator/index.php' target='_blank'>Archive Validator</a> to ensure its contents are valid.
                <a href='../dwc_validator/index.php?file_url=$final_archive_gzip_url' target='_blank'>Click here</a> to validate now.<br><br>
                Thank you. &nbsp;<a href='index.php'>Try another</a>";
            echo "<br>=======================================================<br><br>";
        }
        else {
            echo "There were problems processing this file:<br><br>";
            // from orig tool, not here.
            // if($errors) {
            //     foreach($errors as $error) echo "$error<br>";
            // }
            // else echo "An unknown error occurred<br>";
        }
        // echo "<a href='../../applications/tools.php'>Back to Tools</a>";
    }
    else {
        $ctrler->display_message(array('type' => "highlight", 'msg' => "Build is in unknown state xyz. &nbsp; $str"));
        $ctrler->display_message(array('type' => "highlight", 'msg' => "ERROR: 'from' file does not exist [$from]."));
    }
    */
}
if($build_status) echo "<hr><b>Build status:</b><pre>".$build_status."</pre><hr>";
else {
    if($ctrler->is_task_in_queue('xls2dwca_job', $params['uuid'].$postfix)) { //job_name = 'specmnXport_job' or 'xls2dwca_job'
        echo "<hr><b>Build status:</b><pre>This job is now in queue...</pre><hr>";
    }
    else echo "<hr><b>Build status:</b><pre>Preparing files...</pre><hr>";
}
if($build_status) {
    if($ctrler->is_build_aborted($build_status)) echo "<p>Process aborted. &nbsp; <a href='index.php'>&lt;&lt; Back to main</a>";
}
?>