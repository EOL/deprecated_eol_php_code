<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

ini_set("memory_limit","5000M");
$file = "" . $_GET["file"];
$orig_file = "" . $_GET["orig_file"];

// echo "<pre>";
// echo "<br>$file<br>";
// echo "<br>$orig_file<br>";
// echo "</pre>";
// exit;

if(pathinfo($file, PATHINFO_EXTENSION) == "zip")
{
    $filenamez = pathinfo($file, PATHINFO_FILENAME); //time() e.g. 1493906650
    $extensionz = get_ext_of_orig_file_in_zip($orig_file);
    // exit("[$filenamez]");
    
    $destination = "temp/".$filenamez;
    mkdir($destination);
    
    $zip = new \ZipArchive;
    $res = $zip->open($file);
    // if($res === TRUE)
    if($res)
    {
        // $zip->extractTo('temp/');
        $zip->extractTo($destination);      echo "<br>Zip file extracted...<br>";
        $zip->close();
        unlink("temp/$filenamez".".zip");   echo "<br>Zip file deleted...<br>";
        
        foreach (glob("$destination/*.*") as $filename) //source
        {
            // echo "<br>file = [$filename]<br>";
            $file = "temp/" . "$filenamez.$extensionz"; //destination
            if(!copy($filename, $file)) exit("<hr>Failed to copy file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
            else recursive_rmdir($destination);
            break;
        }
        
    } 
    else 
    {
        echo "<br>There is a problem with the .ZIP file! [temp/" . $filenamez . ".zip]<br>";
        return;
    }
}

require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility();

if($info = $func->tool_generate_higherClassification($file))
{
    $filename = "temp/" . pathinfo($file, PATHINFO_BASENAME);
    $domain = $_SERVER['HTTP_HOST'];
    $temp   = $_SERVER['SCRIPT_NAME'];
    $temp   = str_ireplace("generate.php", $filename, $temp);
    $url    = "http://$domain" . $temp;

    /* utility
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries(pathinfo($filename, PATHINFO_FILENAME), true, $file); //true means output will write to text file
    */
    $undefined_parents = array();

    print"<b>
    Conversion completed. <br>&nbsp;<br>
    This is the URL of the converted file [<i>$orig_file</i>] with higherClassification:<br><br> <a target='$filename' href='$url'>$url</a>
    <br><hr></b>";
    
    if($undefined_parents)
    {
        echo "Undefined parents found: " . count($undefined_parents) . "<br>";
        echo "Report <a href='../content_server/resources/" . pathinfo($filename, PATHINFO_FILENAME) . "_undefined_parent_ids.txt'>here</a><hr>";
    }

    echo "<a href='javascript:history.go(-1)'> &lt;&lt; Back to main</a><br><hr>";
}
else
{
    echo "The file is not ready for processing. The file needs the minimum three fields column header: '<i>taxonID</i>', '<i>scientificName</i>' and '<i>parentNameUsageID</i>'.
    <br><a href='javascript:history.go(-1)'> &lt;&lt; Go back</a>
    <br><hr>";
}

function get_ext_of_orig_file_in_zip($orig)
{
    $temp = pathinfo($orig, PATHINFO_FILENAME);
    return pathinfo($temp, PATHINFO_EXTENSION);
}
?>