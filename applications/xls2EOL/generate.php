<?php
include_once(dirname(__FILE__) . "/../../config/environment.php");

//error_reporting(0);
ini_set("memory_limit","1000M");
//$file = "../xls2EOL/eol.xls";
$file = "" . $_GET["file"];

require_library('XLSParser');
$parser = new XLSParser();

$xml = $parser->create_eol_xml($parser,$file);

/*
foreach($arr['Taxon Name'] as $sci)
{
    print utf8_decode($sci) . "<br>";    
}
*/

/* working but commented because we want to point to an XML file
header('Content-type: text/xml');    
print $xml;
*/

$filename = "xml/" . time() . ".xml";
$OUT = fopen($filename, "w+");            
fwrite($OUT, $xml); fclose($OUT);

$domain = $_SERVER['HTTP_HOST'];
$temp 	= $_SERVER['SCRIPT_NAME'];
$temp 	= str_ireplace("generate.php", $filename, $temp);
$url    = "http://$domain" . $temp;

print"
Conversion completed. <br>&nbsp;<br>
This is the URL of your XML: <a href='$url'>$url</a> <br>&nbsp;<br>
You can use this as your resource URL in the EOL content partner registry (Resources section) or you can save your XML and store it elsewhere. <br>&nbsp;<br>
This XML will be stored in our server for two (2) weeks then it will be removed. <br>&nbsp;<br>
Thank you.
";





//print"<META HTTP-EQUIV='Refresh' Content='0; URL=$filename'>";


?>