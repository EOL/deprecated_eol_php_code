<?php
namespace php_active_record;
/* IRMNG data
   execution time: 2.23 hours; 2,032,569 records to process

            excl '' status  fixed parent-child
as of:      Aug28       Aug31       Sep7
taxon       1,925,615   1,933,060   1,878,575   1,425,833
occurrence  3,938,768   3,938,768   3,938,768   3,938,768
measurement 3,938,768   3,938,768   3,938,768   3,938,768

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IrmngAPI');
$timestart = time_elapsed();
$resource_id = 741;
$func = new IrmngAPI($resource_id);
// $func->get_taxa_without_status_but_with_eol_page(); //utility
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>