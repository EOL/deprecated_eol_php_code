<?php
include_once(dirname(__FILE__) . "/../../config/environment.php");

error_reporting(0);

//$file = "../xls2EOL/eol_specialist_project.xls";
$file = "" . $_GET["file"];

require_library('XLSParser');
$parser = new XLSParser();
$arr = $parser->convert_sheet_to_array($file);          
$xml = $parser->create_specialist_project_xml($arr);

/* working but commented because we want to point to an XML file
header('Content-type: text/xml');    
print $xml;
*/

$filename = "xml/" . time() . ".xml";
$OUT = fopen($filename, "w+");            
fwrite($OUT, $xml); fclose($OUT);

print"<META HTTP-EQUIV='Refresh' Content='0; URL=$filename'>";


?>