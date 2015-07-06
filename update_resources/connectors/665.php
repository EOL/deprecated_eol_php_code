<?php
namespace php_active_record;
/* PESI
estimated execution time:
    4.5 hours - generation of taxa list text file
    2 days - generation of 5 processed text files - save_data_to_text()
    2 days - generation of 5 archive files - process_text_file()
*/
return;
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PesiAPI');
$timestart = time_elapsed();
$resource_id = "665";
$func = new PesiAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>