<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BHL_Download_API');
$GLOBALS['ENV_DEBUG'] = false;
$timestart = time_elapsed();
$func = new BHL_Download_API();

/* e.g. php complete_BHL_name.php _ "A. scybalarius" */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$str                                = @$argv[2]; //useful here

$str    = htmlspecialchars($_GET["name"]);
$pageID = htmlspecialchars(@$_GET["pageID"]);

// $str = "M. ternaria";//"G. vernalis";//"G. stercorosus";//"G. pyrenaeus";
$complete_name = $func->complete_name($str, $pageID);
$arr = array('complete_name' => $complete_name);
print(json_encode($arr));
// exit("\n[$complete_name]\nstop muna\n");
?>