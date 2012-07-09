<?php
namespace php_active_record;
/* connector for BOLDS
estimated execution time: 15 hours for slow connection
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BoldsAPI');

// It is assumed that this file already exists: DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt"
$folder = DOC_ROOT . "update_resources/connectors/files/BOLD";
if(!file_exists($folder)) mkdir($folder , 0777);

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

$resource_id = 81;
$bolds = new BoldsAPI();
$bolds->initialize_text_files();
$bolds->start_process($resource_id, false);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");

/* Curl error during harvest
1863 -- Polynoinae (http://www.boldsystems.org/views/taxbrowser.php?taxid=194200): 
2206 -- Australonura (http://www.boldsystems.org/views/taxbrowser.php?taxid=139991): 
2207 -- Bilobella (http://www.boldsystems.org/views/taxbrowser.php?taxid=96797): 
2208 -- Blasconura (http://www.boldsystems.org/views/taxbrowser.php?taxid=140158): 
2209 -- Cansilianura (http://www.boldsystems.org/views/taxbrowser.php?taxid=140155): 
2210 -- Catalanura (http://www.boldsystems.org/views/taxbrowser.php?taxid=248689):  
2211 -- Christobella (http://www.boldsystems.org/views/taxbrowser.php?taxid=271258): 
2212 -- Coreanura (http://www.boldsystems.org/views/taxbrowser.php?taxid=96681):  
2213 -- Crossodonthina (http://www.boldsystems.org/views/taxbrowser.php?taxid=297358): 
2214 -- Cryptonura (http://www.boldsystems.org/views/taxbrowser.php?taxid=360885): 
2215 -- Deutonura (http://www.boldsystems.org/views/taxbrowser.php?taxid=96732): 
2216 -- Ectonura (http://www.boldsystems.org/views/taxbrowser.php?taxid=271253): 
2217 -- Edoughnura (http://www.boldsystems.org/views/taxbrowser.php?taxid=248685): 
2218 -- Endonura (http://www.boldsystems.org/views/taxbrowser.php?taxid=140141): 
2219 -- Gnatholonche (http://www.boldsystems.org/views/taxbrowser.php?taxid=96782): 
2220 -- Hyperlobella (http://www.boldsystems.org/views/taxbrowser.php?taxid=96778): 
2221 -- Lathriopyga (http://www.boldsystems.org/views/taxbrowser.php?taxid=174934): 
2222 -- Lobellina (http://www.boldsystems.org/views/taxbrowser.php?taxid=178045): 
*/
?>