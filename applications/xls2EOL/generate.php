<?php
include_once(dirname(__FILE__) . "/../../config/environment.php");

error_reporting(0);
ini_set("memory_limit","1000M");
$file = "" . $_GET["file"];

require_library('XLSParser');
$parser = new XLSParser();

$xml = $parser->create_eol_xml($parser,$file);

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


$validate = get_val_var('validate');
if($validate == 'on')
{    
    print"<hr><p>
    <form name='validator_form' action='http://services.eol.org/eol_php_code/applications/validator/index.php' method='post'>
    <input type='hidden' size='30' name='file_url' value='$url'>
    <input type='submit' value='Click here to Validate >> '>
    </td></form>
    <p><a href='javascript:history.go(-1)'> &lt;&lt; Back to menu</a>";    
    exit;        
}


function get_val_var($v)
{
    if     (isset($_GET["$v"]))$var=$_GET["$v"];
    elseif (isset($_POST["$v"]))$var=$_POST["$v"];    
    if(isset($var)) return $var;
    else return NULL;
}

?>