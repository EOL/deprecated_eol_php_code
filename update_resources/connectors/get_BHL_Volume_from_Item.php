<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
$GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();
$func = new BHL_Download_API();

/* 
e.g. php complete_BHL_name.php _ "A. scybalarius"
http://localhost/eol_php_code/update_resources/connectors/get_BHL_Volume_from_Item.php?pageID=25337279
*/

/* not used here...
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$str                                = @$argv[2]; //useful here
*/

// $str    = htmlspecialchars(@$_GET["name"]);
$pageID = htmlspecialchars(@$_GET["pageID"]);
$volume = $func->get_Item_Volume_via_PageID($pageID);
$arr = array('volume' => $volume);
print(json_encode($arr));
// exit("\n[$complete_name]\nstop muna\n");
?>