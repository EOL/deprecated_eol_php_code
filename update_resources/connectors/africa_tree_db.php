<?php
namespace php_active_record;
/* DATA-1792 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AfricaTreeDBAPI');
$timestart = time_elapsed();

$dwca_file = "https://opendata.eol.org/dataset/e31baa95-af6c-4539-a1d8-00f7364fadcd/resource/c39822fe-79a1-4fd9-929f-0ca6e495f3b4/download/archive.zip";
$resource_id = "atdb"; //africa tree db
$func = new AfricaTreeDBAPI($resource_id, $dwca_file);

/* utilities - to get the un-mapped strings
$func->get_unmapped_strings();
exit("\n-end utility-\n");
*/

$func->convert_archive(); //main operation
Functions::finalize_dwca_resource($resource_id, false);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
