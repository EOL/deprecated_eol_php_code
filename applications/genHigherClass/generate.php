<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set("memory_limit","1000M");
$file = "" . $_GET["file"];
echo "<br>$file<br>";


require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility();

$info = $func->tool_generate_higherClassification($file);



/*
require_library('XLSParser');
$parser = new XLSParser();
$xml = $parser->create_eol_xml($file);

$filename = "xml/" . time() . ".xml";
if(!($OUT = fopen($filename, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
}
else
{
    fwrite($OUT, $xml); 
    fclose($OUT);
}
*/

exit;

/*
$domain = $_SERVER['HTTP_HOST'];
$temp   = $_SERVER['SCRIPT_NAME'];
$temp   = str_ireplace("generate.php", $filename, $temp);
$url    = "http://$domain" . $temp;

print"
Conversion completed. <br>&nbsp;<br>
This is the URL of your XML: <a href='$url'>$url</a> <br>&nbsp;<br>
You can use this as your resource URL in the EOL content partner registry (Resources section) or you can save your XML and store it elsewhere. <br>&nbsp;<br>
This XML will be stored in our server for two (2) weeks then it will be removed. <br>&nbsp;<br>
Thank you.";
*/

?>