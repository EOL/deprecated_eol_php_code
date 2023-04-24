<?php
namespace php_active_record;
/* this will get all the scientificNames and corresponding page nos. from the different PDF files for Kubitzki. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/Kubitzki_PageNosAPI');
$func = new Kubitzki_PageNosAPI();
$func->start();

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>