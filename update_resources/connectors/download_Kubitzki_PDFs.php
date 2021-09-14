<?php
namespace php_active_record;
/* DATA-1894: Kubitzki et al
a script that downloads all PDFs for this resource
- then generates its .txt counter part ---> id_raw.txt
- then massages it to add a blank row ---> id.txt
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Functions_Memoirs');
$timestart = time_elapsed();

$func = new Functions_Memoirs();
$func->download_Kubitzki_pdfs();
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>