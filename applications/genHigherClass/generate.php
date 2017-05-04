<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set("memory_limit","3000M");
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

    print"
    Conversion completed. <br>&nbsp;<br>
    This is the URL of your new [<i>$orig_file</i>] with higherClassification: <a target='$filename' href='$url'>$url</a> <br>&nbsp;<br>
    Thank you.<br><hr>";
}
else
{
    echo "The file is not ready for processing. The file needs the minimum three fields: '<i>taxonID</i>', '<i>scientificName</i>' and '<i>parentNameUsageID</i>'.
    <br><hr>";
}

?>