<?php
$job_name = 'xls2dwca_job';
$postfix = "_xls2dwca";
$str = "Copy 'Build status' and send to <i>eagbayani@eol.org</i>. &nbsp; <a href='index.php'>&lt;&lt; Back to main</a>";
// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid'].$postfix);
if($ctrler->did_build_fail($build_status)) {
    $ctrler->display_message(array('type' => "error", 'msg' => "Process failed. &nbsp; $str"));
}
elseif($ctrler->is_build_currently_running($build_status)) {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing... Page will refresh every 5 seconds."));
    /* Very important variable is: $path below */
    /* IMPORTANT: take note that adding params to task_status.php at the end is erroneous. ADD NEW PARAMS where e.g. Proj_and_Taxon is added. */
    $path = "task_status.php?task=$task&uuid=$params[uuid]&Proj_and_Taxon=$params[Proj_and_Taxon]&destination=".urlencode($params['destination'])."&true_root=".urlencode($params['true_root']);
    $ctrler->display_message(array('type' => "highlight", 'msg' => "OR you can check back later. &nbsp; You can use this <a href='$path'>link to check status</a> anytime."));
    $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $actual_link = str_ireplace("form_result.php", $path, $actual_link);
    echo "<meta http-equiv='refresh' content='5;url=".$actual_link."'>";
    // return;
}
else {
    // echo "<pre>"; print_r($params); echo "</pre><hr>elix"; //good debug
    /* Array(  [task] => xls2dwca_job_1
               [uuid] => 1574952226
               [destination] => /opt/homebrew/var/www/eol_php_code//applications/specimen_image_export/temp/1574952226.xls
               [true_root] => /opt/homebrew/var/www/eol_php_code/)

Array
(
    [true_root] => /opt/homebrew/var/www/eol_php_code/
    [uuid] => 
    [json] => {"Proj":"KANB", "Taxon":"Pomacentrinae", "Dept":"FISH", "Lic":"CreativeCommons – Attribution Non-Commercial (by-nc)", "Lic_yr":"", "Lic_inst":"", "Lic_cont":""}
    [destination] => /opt/homebrew/var/www/eol_php_code//applications/specimen_image_export/
)

    */
    /* Delete temp files */
    $dirname = pathinfo($params['destination'], PATHINFO_DIRNAME).'/'; //obsolete
    $dirname = $params['true_root'].'applications/specimen_image_export/temp/';
    $extensions = array('.csv', '.zip'); //possible extensions
    foreach($extensions as $ext) {
        $tmp = $dirname.$params['uuid'].$ext;
        if(file_exists($tmp)) {
            if(unlink($tmp)) {} //$ctrler->display_message(array('type' => "highlight", 'msg' => "Deleted: [$tmp]"));
            else $ctrler->display_message(array('type' => "error", 'msg' => "ERROR: cannot delete [$tmp]"));
        }
        else {} //$ctrler->display_message(array('type' => "highlight", 'msg' => "Does not exist [$tmp]"));
    }

    /* Summary message */
    // /*
    /* from copied template. 
    $final_archive_gzip_url = CONTENT_RESOURCE_LOCAL_PATH . "MarineGEO_sie/" . $params['uuid'] . ".xls";
    if(file_exists($final_archive_gzip_url)) {
    */
    if(true) { //todo: create your Success criteria event
        $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
        // if($final_archive_gzip_url) {
        if(true) {
            // $final_archive_gzip_url = str_replace(DOC_ROOT, WEB_ROOT, $final_archive_gzip_url);
            echo "=======================================================<br>";
            // print_r($params);
            echo "Process done.<br><br>
                <a href='../../../other_files/KatieO_CSV/summary/".$params['Proj_and_Taxon'].".tsv'>See summary report.</a><br><br>
                <a href='index.php'>Back to menu</a>";
            echo "<br>=======================================================<br><br>";
        }
        else {
            echo "There were problems processing this file:<br><br>";
        }
        // echo "<a href='../../applications/tools.php'>Back to Tools</a>";
    }
    else {
        $ctrler->display_message(array('type' => "highlight", 'msg' => "ERROR: Build failed. &nbsp; $str"));
    }
    // */
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