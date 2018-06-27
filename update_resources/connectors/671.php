<?php
namespace php_active_record;
/* MycoBank Classification - new spreadsheet download (TRAM-788)
estimated execution time: 
http://www.eol.org/content_partners/614/resources/671
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MycoBankAPI');
$timestart = time_elapsed();
$resource_id = 671;
$func = new MycoBankAPI($resource_id);

// /* utilities only, comment in real operation
// $func->saving_ids_2text(); exit; // utility only, run only once
$func->access_text_for_caching(); exit; //utility only, for caching
// */

// $func->start();

Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>