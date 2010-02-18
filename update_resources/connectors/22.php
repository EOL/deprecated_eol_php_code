<?php

//define("MYSQL_DEBUG", true);
$path = "";
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$new_resource_path = LOCAL_ROOT . "temp/22.xml.gz";

$new_resource = Functions::get_remote_file("http://animaldiversity.ummz.umich.edu/XML/adw_eol.xml.gz");

$OUT = fopen($new_resource_path, "w+");
fwrite($OUT, $new_resource);
fclose($OUT);

shell_exec("gunzip -f ".$new_resource_path);
$new_resource_path = LOCAL_ROOT . "temp/22.xml";

$xml = file_get_contents($new_resource_path);
$xml = str_replace("<dc:description>", "<dc:description><![CDATA[", $xml);
$xml = str_replace("</dc:description>", "]]></dc:description>", $xml);
$xml = preg_replace("/<a>([^<]+)<\/a>/", "\\1", $xml);
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $xml;


$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "22.xml";

$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

shell_exec("rm ".$new_resource_path);


?>