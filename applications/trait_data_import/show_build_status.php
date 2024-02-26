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
    $dirname = $params['true_root'].'applications/trait_data_import/temp/';
    $extensions = array('.xlsx', '.xls', '.zip'); //possible extensions
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
    $final_archive_gzip_url = CONTENT_RESOURCE_LOCAL_PATH . "Trait_Data_Import/" . $params['uuid'] . ".tar.gz";
    if(file_exists($final_archive_gzip_url)) {
        
        // /* NEW: to accommodate Filename_ID implementation
        if($Filename_ID = @$params['Filename_ID']) {
            $rename_to = CONTENT_RESOURCE_LOCAL_PATH . "Trait_Data_Import/" . $params['Filename_ID'] . ".tar.gz";
            /*
            Functions::file_rename($final_archive_gzip_url, $rename_to);
            */

            // /* ========== New: since file_rename() above doesn't work
            if(copy($final_archive_gzip_url, $rename_to)) echo "<br>Updated OK: [$rename_to]<br>";
            else echo "\n<br>ERRORx: cannot copy [$final_archive_gzip_url] to [$rename_to]<br>Please inform eagbayani@eol.org.<br>\n";

            /* option 1 - worked in local but not in eol-archive
            $rename_tmp = str_replace("/Trait_Data_Import/", "/Trait_Data_Import/tmp_", $final_archive_gzip_url);
            if(rename($final_archive_gzip_url, $rename_tmp)) echo "<br>Renamed OK: [$rename_tmp]<br>";
            else echo "<br>Rename failed: <br>";
            */

            // option 2 - worked in local but not in eol-archive
            // [/var/www/html/eol_php_code/applications/content_server/resources/Trait_Data_Import/1686023312.tar.gz] -> $final_archive_gzip_url
            // if(unlink($final_archive_gzip_url)) echo "<br>Deleted OK 1: [$tmp]<br>";
            // else {
            //     echo "\n<br>ERRORx: cannot unlink [$final_archive_gzip_url]<br>\n";
            //     $tmp = str_replace("/var/www/html/", "/html/", $final_archive_gzip_url);
            //     $out = shell_exec("rm -f ".$tmp); echo "\nTerminal: [$out]\n";
            //     if(is_file($final_archive_gzip_url)) echo "<br>Still not deleted 1 [$final_archive_gzip_url]<br>";
            //     else                                 echo "<br>Deleted OK 2: [$final_archive_gzip_url]<br>";

            //     $tmp = $final_archive_gzip_url;
            //     $out = shell_exec("rm -f ".$tmp); echo "\nTerminal: [$out]\n";
            //     if(is_file($final_archive_gzip_url)) echo "<br>Still not deleted 2 [$final_archive_gzip_url]<br>";
            //     else                                 echo "<br>Deleted OK 3: [$final_archive_gzip_url]<br>";
            // }
            // ========== */

            $final_archive_gzip_url = $rename_to;
            $hash_post = $Filename_ID;
        }
        else $hash_post = $params['uuid'];
        // */
        
        // /* special provision
        $ckan_resource_id = get_ckan_resource_id_given_hash("hash-".$hash_post);
        // */
        // echo "\n" . getcwd() . "\n"; exit;

        $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
        
        if($final_archive_gzip_url && $ckan_resource_id) {
            $final_archive_gzip_url = str_replace(DOC_ROOT, WEB_ROOT, $final_archive_gzip_url);
            $opendata = "https://opendata.eol.org/dataset/trait-spreadsheet-repository/resource/".$ckan_resource_id;
            
            echo "=======================================================<br>";
            echo "The DwCA is now available in <a href='$final_archive_gzip_url'>$final_archive_gzip_url</a><br><br>
                You can save this file to your computer.<br><br>
                The OpenData resource for this upload is <a target='$hash_post' href='$opendata'>here</a><br><br>
                The Upload ID is: <b>$hash_post</b>. You will use this if you want to re-upload an updated spreadsheet.<br><br>";
                
                // /* NEW: Feb 21, 2022 --- invalid mValues report
                $resources_path = CONTENT_RESOURCE_LOCAL_PATH."Trait_Data_Import/";
                $filename = $resources_path.$params['uuid']."_invalid_values.txt";
                if(file_exists($filename) && filesize($filename) > 0) {
                    $href = str_replace(DOC_ROOT, WEB_ROOT, $filename);
                    echo "<a target='".$params['uuid']."'  href='$href'>Invalid measurement values</a><br><br>";
                }
                // else unlink($filename); //works locally but has permission errors in eol-archive
                // */
                echo "<a href='main.php'>Back to menu</a>";
            echo "<br>=======================================================<br><br>";
            // echo "<pre>"; print_r($params); echo "</pre>"; //good debug
            /*Array(
                [task] => xls2dwca_job_1
                [uuid] => 1643726800
                [destination] => /opt/homebrew/var/www/eol_php_code//applications/trait_data_import/temp/1643726800.xlsx
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
if($build_status) echo "<hr><b>Build status:</b><pre>".$build_status."</pre><hr>";
else {
    if($ctrler->is_task_in_queue('xls2dwca_job', $params['uuid'].$postfix)) { //job_name = 'specmnXport_job' or 'xls2dwca_job'
        echo "<hr><b>Build status:</b><pre>This job is now in queue...</pre><hr>";
    }
    else echo "<hr><b>Build status:</b><pre>Preparing files...</pre><hr>";
}
if($build_status) {
    if($ctrler->is_build_aborted($build_status)) echo "<p>Process aborted. &nbsp; <a href='main.php'>&lt;&lt; Back to main</a>";
}

function get_ckan_resource_id_given_hash($hash)
{
    $ckan_resources = get_opendata_resources_given_datasetID("trait-spreadsheet-repository");
    // echo "<pre>"; print_r($ckan_resources); echo "<br>[$hash]</pre>"; //good debug
    /*Array(
        [0] => stdClass Object(
                [cache_last_updated] => 
                [cache_url] => 
                [mimetype_inner] => 
                [hash] => cha_02
                [description] => Updated: 2022-02-02 20:00
                [format] => Darwin Core Archive
                [url] => http://localhost/eol_php_code/applications/content_server/resources/Trait_Data_Import/cha_02.tar.gz
                [created] => 2022-02-03T00:21:26.418199
                [state] => active
                [webstore_last_updated] => 
                [webstore_url] => 
                [package_id] => dab391f0-7ec0-4055-8ead-66b1dea55f28
                [last_modified] => 
                [mimetype] => 
                [url_type] => 
                [position] => 0
                [revision_id] => 52f079cf-fa6f-40ec-a3f2-b826ed3c3885
                [size] => 
                [id] => 6f4d804b-6f49-4841-a84e-3e0b02b35043
                [resource_type] => 
                [name] => cha_02 name
            )*/
    foreach($ckan_resources as $res) {
        if($res->hash == $hash) return $res->id;
    }
    return false;
}
function get_opendata_resources_given_datasetID($dataset, $all_fields = true)
{
    // $options = $this->download_options;
    $options['expire_seconds'] = 0;
    $domain = "https://opendata.eol.org/api/3";     //for old CKAN
    // $domain = "http://localhost:8800/api";          //for new CKAN

    if($json = Functions::lookup_with_cache($domain."/action/package_show?id=".$dataset, $options)) {    
        $o = json_decode($json);
        if($all_fields) return $o->result->resources;
        foreach($o->result->resources as $res) $final[$res->url] = '';
    }
    else {
        echo "\nError message:\n$json\n";
        exit("\nCannot lookup: [$dataset]\n");
    }
    return array_keys($final);
}
?>