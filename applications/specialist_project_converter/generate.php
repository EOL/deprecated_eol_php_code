<?php
include_once(dirname(__FILE__) . "/../../config/environment.php");

//$file = "../xls2EOL/eol_specialist_project.xls";
$file = "" . $_GET["file"];

require_library('XLSParser');
$parser = new XLSParser();
$arr = $parser->convert_sheet_to_array($file);          
$xml = $parser->create_specialist_project_xml($arr);
header('Content-type: text/xml');    
print $xml;

?>