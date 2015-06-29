<?php
namespace php_active_record;
/* 
First client for TraitRequestAPI is: WEB-5987
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/TraitRequestAPI');
$func = new TraitRequestAPI();

$params['name'] = "WEB-5987";
$params['spreadsheet'] = "http://localhost/cp/TraitRequest/WEB-5987/Tree list EOL TraitBank.xlsx";
$params['spreadsheet'] = "https://dl.dropboxusercontent.com/u/7597512/TraitRequest/WEB-5987/Tree list EOL TraitBank.xlsx";
$func->generate_traits_for_taxa($params);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\nelapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>