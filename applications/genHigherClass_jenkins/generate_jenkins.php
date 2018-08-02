<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
// set_time_limit(60*60); //1 hour --- Commented, problematic in MacMini. It doesn't render page, browser just loading... endlessly
ini_set("memory_limit","8000M");
// $GLOBALS['ENV_DEBUG'] = true;

/* Important settings
Apache httpd.conf:
    Timeout 1200
    
php.ini:
    upload_max_filesize = 10M
    post_max_size = 10M
*/

// print_r($argv);
$file      = $argv[1];
$orig_file = @$argv[2];
$server_http_host = @$argv[3];
$server_script_name = @$argv[4];

debug("<br>Working file: [$file]<br>");

if(pathinfo($file, PATHINFO_EXTENSION) == "zip")
{
    $filenamez = pathinfo($file, PATHINFO_FILENAME); //time() e.g. 1493906650
    $extensionz = get_ext_of_orig_file_in_zip($orig_file);
    
    $destination = "temp/".$filenamez;
    mkdir($destination);
    
    if(file_exists($file)) debug("<br>[$file] file exists - OK<br>");
    else {
        echo "<br>[$file] file does not exist - ERROR<br>";
        return;
    }
    
    //start of new routine ================================
    $status = shell_exec("unzip $file -d $destination");    debug("<br>unzip status: <i>$status</i><br>");
    $status = unlink("temp/$filenamez".".zip");             debug("<br>unlink status: <i>$status</i><br>");
    
    foreach (glob("$destination/*.*") as $filename) { //source
        $file = "temp/" . "$filenamez.$extensionz"; //destination
        if(!copy($filename, $file)) exit("<hr>Failed to copy file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
        else recursive_rmdir($destination);
        break;
    }
    //end of new routine ================================
}
else
{
    /* this will zip the uploaded file then delete the uploaded file then unzip it. This is so that Jenkins will own the file to be processed */
    // echo "\n current working dir: ".getcwd()."\n";
    // $file = getcwd()."/".$file;
    if(file_exists($file)) {
        echo "\nOK: File exists: $file\n";
        echo "\n[zip $file.zip $file]\n";
        shell_exec("zip $file.zip $file");
        echo "\n[$file]\n";
        if(unlink($file)) echo "\nFile deleted ($file)\n";
        else echo "\nCannot unlink ($file)\n";
        shell_exec("unzip $file.zip");
        unlink("$file.zip");
    }
    else echo "\nERROR: File does not exist: $file\n";
}

require_library('connectors/DwCA_Utility_cmd');
$func = new DwCA_Utility_cmd();

if($info = $func->tool_generate_higherClassification($file)) {
    $filename = "temp/" . pathinfo($file, PATHINFO_BASENAME);
    $domain = $server_http_host; //$_SERVER['HTTP_HOST'];
    $temp   = $server_script_name; //$_SERVER['SCRIPT_NAME'];
    $temp   = str_ireplace("generate_jenkins.php", $filename, $temp);
    $url    = "http://$domain" . $temp;

    //start zip
    debug("<br>filename = $filename<br>");
    $command_line = "zip -rj " . $filename . ".zip " . $filename;
    $output = shell_exec($command_line);
    //end zip

    /* utility
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries(pathinfo($filename, PATHINFO_FILENAME), true, $file); //true means output will write to text file
    */
    $undefined_parents = array();

    print"<b><br>Conversion completed. 
    <br><br>This is the URL of the converted file [<i>$orig_file</i>] with higherClassification:
    <br><br> <a target='$filename' href='$url'>$url</a>
    <br><br> <a target='$filename' href='$url.zip'>$url.zip</a>
    <br><br> Reminder: These files will be deleted from the server after 24 hours.
    <br><p></b>";
    
    if($undefined_parents) {
        echo "Undefined parents found: " . count($undefined_parents) . "<br>";
        echo "Report <a href='../content_server/resources/" . pathinfo($filename, PATHINFO_FILENAME) . "_undefined_parent_ids.txt'>here</a><hr>";
    }

    echo "<a href='index.php'>&lt;&lt; Back to main</a><br><p>";
}
else {
    echo "The file is not ready for processing. The file needs the minimum three fields column header: '<i>taxonID</i>', '<i>scientificName</i>' and '<i>parentNameUsageID</i>'.
    <br><a href='index.php'>&lt;&lt; Go back</a>
    <br><p>";
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "elapsed time = $elapsed_time_sec seconds                  ";
echo "<br>elapsed time = " . $elapsed_time_sec/60 . " minutes   ";
echo "<br>Done processing.<br><br>";

function get_ext_of_orig_file_in_zip($orig)
{
    $temp = pathinfo($orig, PATHINFO_FILENAME);
    return pathinfo($temp, PATHINFO_EXTENSION);
}
?>