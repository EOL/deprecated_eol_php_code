<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set("memory_limit","5000M");
$file = "" . $_GET["file"];
$orig_file = "" . $_GET["orig_file"];
// echo "<br>$file<br>";

require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility();

if($info = $func->tool_generate_higherClassification($file))
{
    $filename = "temp/" . pathinfo($file, PATHINFO_BASENAME);
    $domain = $_SERVER['HTTP_HOST'];
    $temp   = $_SERVER['SCRIPT_NAME'];
    $temp   = str_ireplace("generate.php", $filename, $temp);
    $url    = "http://$domain" . $temp;

    
    // /* utility
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries(pathinfo($filename, PATHINFO_FILENAME), true, $file); //true means output will write to text file
    // */


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

?>