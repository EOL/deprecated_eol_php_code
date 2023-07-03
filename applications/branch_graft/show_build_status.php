<?php
$job_name = 'xls2dwca_job';
$postfix = "_xls2dwca";
$str = "Copy 'Build status' and send to <i>eagbayani@eol.org</i>. &nbsp; <a href='main.php'>&lt;&lt; Back to main</a>";
// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid'].$postfix);
if($ctrler->did_build_fail($build_status)) {
    $ctrler->display_message(array('type' => "error", 'msg' => "Process failed. &nbsp; $str"));
}
elseif($ctrler->is_build_currently_running($build_status)) {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing... Page will refresh every 5 seconds."));
    /* Very important variable is: $path below */
    $path = "task_status.php?task=$task&uuid=$params[uuid]&destination=".urlencode($params['destination'])
        ."&true_root=".urlencode($params['true_root'])
        ."&Filename_ID=".urlencode($params['Filename_ID']);
    $ctrler->display_message(array('type' => "highlight", 'msg' => "OR you can check back later. &nbsp; You can use this <a href='$path'>link to check status</a> anytime."));
    $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $actual_link = str_ireplace("form_result.php", $path, $actual_link);
    echo "<meta http-equiv='refresh' content='5;url=".$actual_link."'>";
    // return;
}
else {
    // echo "<pre>"; print_r($params); echo "</pre><hr>"; //good debug
    /* Array(  [task] => xls2dwca_job_1
               [uuid] => 1574952226
               [destination] => /opt/homebrew/var/www/eol_php_code//applications/specimen_image_export/temp/1574952226.xls
               [true_root] => /opt/homebrew/var/www/eol_php_code/)
    */
    /* Delete temp files */
    $dirname = pathinfo($params['destination'], PATHINFO_DIRNAME).'/'; //obsolete
    // $dirname = $params['true_root'].'applications/specimen_image_export/temp/';
    // $dirname = $params['true_root'].'applications/trait_data_import/temp/';
    $dirname = $params['true_root'].'applications/taxonomic_validation/temp/';
    $extensions = array('.tab', '.txt', '.tsv', '.zip', '.tmp', '.gz', '.csv'); //possible extensions
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
    // $final_archive_gzip_url = CONTENT_RESOURCE_LOCAL_PATH . "MarineGEO_sie/" . $params['uuid'] . ".xls";
    // $final_archive_gzip_url = CONTENT_RESOURCE_LOCAL_PATH . "Trait_Data_Import/" . $params['uuid'] . ".tar.gz";
    $final_archive_gzip_url = CONTENT_RESOURCE_LOCAL_PATH . "Taxonomic_Validation/" . $params['uuid'] . ".zip";
    if(file_exists($final_archive_gzip_url)) {
        
        // /* NEW: to accommodate Filename_ID implementation
        if($Filename_ID = @$params['Filename_ID']) {
            // $rename_to = CONTENT_RESOURCE_LOCAL_PATH . "Trait_Data_Import/" . $params['Filename_ID'] . ".tar.gz";
            $rename_to = CONTENT_RESOURCE_LOCAL_PATH . "Taxonomic_Validation/" . $params['Filename_ID'] . ".zip";

            /* as much as possible don't use: Functions::file_rename
            Functions::file_rename($final_archive_gzip_url, $rename_to)
            */
            
            // /* ========== New: since Functions::file_rename above doesn't work
            if(copy($final_archive_gzip_url, $rename_to)) echo "<br>Updated OK: [".pathinfo($rename_to, PATHINFO_BASENAME)."]<br>";
            else echo "\n<br>ERRORx: cannot copy [$final_archive_gzip_url] to [$rename_to]<br>Please inform eagbayani@eol.org.<br>\n";
            // ========== */

            $final_archive_gzip_url = $rename_to;
            $hash_post = $Filename_ID;
        }
        else $hash_post = $params['uuid'];
        // */
        
        /* special provision : copied template
        $ckan_resource_id = get_ckan_resource_id_given_hash("hash-".$hash_post);
        */
        // echo "\n" . getcwd() . "\n"; exit;

        $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
        
        // if($final_archive_gzip_url && $ckan_resource_id) {
        if($final_archive_gzip_url) {        
            $final_archive_gzip_url = str_replace(DOC_ROOT, WEB_ROOT, $final_archive_gzip_url);
            // $opendata = "https://opendata.eol.org/dataset/trait-spreadsheet-repository/resource/".$ckan_resource_id;
            
            echo "=======================================================<br>";
            echo "The report is now available in <a href='$final_archive_gzip_url'>$final_archive_gzip_url</a><br><br>
                You can save this file to your computer.<br><br>
                This file will remain in our server for two (2) weeks. ";                
                echo "<a href='main.php'>Back to menu</a>";
            echo "<br>=======================================================<br><br>";
            // echo "<pre>"; print_r($params); echo "</pre>"; //good debug
            /*Array(
                [task] => xls2dwca_job_1
                [uuid] => 1643726800
                [destination] => /opt/homebrew/var/www/eol_php_code//applications/trait_data_import/temp/1643726800.xlsx
                [destination] => /opt/homebrew/var/www/eol_php_code//applications/taxonomic_validation/temp/1643726800.tab
                [true_root] => /opt/homebrew/var/www/eol_php_code/
                [Filename_ID] => 111222
            )*/
        }
        else {
            echo "There were problems processing this upload: [$final_archive_gzip_url] [$ckan_resource_id]"; print_r($params);
        }
        // echo "<a href='../../applications/tools.php'>Back to Tools</a>";
    }
    else {
        $ctrler->display_message(array('type' => "highlight", 'msg' => "ERROR: Build failed. &nbsp; $str"));
        // $ctrler->display_message(array('type' => "highlight", 'msg' => "ERROR: File does not exist [$final_archive_gzip_url].")); //during development only
    }
    // */
}
if($build_status) echo "<hr><b>Build status 3:</b><pre>".$build_status."</pre><hr>";
else {
    if($ctrler->is_task_in_queue('xls2dwca_job', $params['uuid'].$postfix)) { //job_name = 'specmnXport_job' or 'xls2dwca_job'
        echo "<hr><b>Build status 1:</b><pre>This job is now in queue...</pre><hr>";
    }
    else echo "<hr><b>Build status 2:</b><pre>Preparing files...</pre><hr>";
}
if($build_status) {
    if($ctrler->is_build_aborted($build_status)) echo "<p>Process aborted. &nbsp; <a href='main.php'>&lt;&lt; Back to main</a>";
}
/* copied template
function get_ckan_resource_id_given_hash($hash)
{
    $ckan_resources = get_opendata_resources_given_datasetID("trait-spreadsheet-repository");
    // echo "<pre>"; print_r($ckan_resources); echo "<br>[$hash]</pre>"; //good debug
    // Array(
    //     [0] => stdClass Object(
    //             [cache_last_updated] => 
    //             [cache_url] => 
    //             [mimetype_inner] => 
    //             [hash] => cha_02
    //             [description] => Updated: 2022-02-02 20:00
    //             [format] => Darwin Core Archive
    //             [url] => http://localhost/eol_php_code/applications/content_server/resources/Trait_Data_Import/cha_02.tar.gz
    //             [created] => 2022-02-03T00:21:26.418199
    //             [state] => active
    //             [webstore_last_updated] => 
    //             [webstore_url] => 
    //             [package_id] => dab391f0-7ec0-4055-8ead-66b1dea55f28
    //             [last_modified] => 
    //             [mimetype] => 
    //             [url_type] => 
    //             [position] => 0
    //             [revision_id] => 52f079cf-fa6f-40ec-a3f2-b826ed3c3885
    //             [size] => 
    //             [id] => 6f4d804b-6f49-4841-a84e-3e0b02b35043
    //             [resource_type] => 
    //             [name] => cha_02 name
    //         )
    foreach($ckan_resources as $res) {
        if($res->hash == $hash) return $res->id;
    }
    return false;
}
function get_opendata_resources_given_datasetID($dataset, $all_fields = true)
{
    // $options = $this->download_options;
    $options['expire_seconds'] = 0;
    if($json = Functions::lookup_with_cache("https://opendata.eol.org/api/3/action/package_show?id=".$dataset, $options)) {
        $o = json_decode($json);
        if($all_fields) return $o->result->resources;
        foreach($o->result->resources as $res) $final[$res->url] = '';
    }
    else exit("\ncannot lookup\n");
    return array_keys($final);
}
*/
?>