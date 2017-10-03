<?php
namespace php_active_record;
/* Botanic Garden and Botanical Museum Berlin-Dahlem, Europeana collection
estimated execution time: 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BotanicalEuropeanaAPI');
$timestart = time_elapsed();
$resource_id = 111;
$func = new BotanicalEuropeanaAPI($resource_id);


// $func->save_before_site_goes_dark(); exit; //utility
// $func->move_files(); exit;
/* 
$func->unlink_files(); exit; //utility 
*/

// $func->resize_downloaded_images(); // utility
// $func->delete_broken_files(); // utility
$func->match_big_with_small_images(); // utility
exit;

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>