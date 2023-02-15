<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
error_reporting(0);
ini_set("memory_limit","1000M");
$file = "" . $_GET["file"];

require_library('XLSParser');
$parser = new XLSParser();
$xml = $parser->create_eol_xml($file);

$filename = "xml/" . time() . ".xml";
if(!($OUT = fopen($filename, "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
}else{
  fwrite($OUT, $xml); fclose($OUT);
}

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

$validate = $_GET['validate'];
if($validate == 'on')
{    
    print"<hr><p>
    <form name='validator_form' action='http://services.eol.org/eol_php_code/applications/validator/main.php' method='post'>
    <input type='hidden' size='30' name='file_url' value='$url'>
    <input type='submit' value='Click here to Validate >> '>
    </td></form>
    <p><a href='javascript:history.go(-1)'> &lt;&lt; Back to menu</a>";
    exit;
}
?>