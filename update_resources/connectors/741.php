<?php
namespace php_active_record;
/* IRMNG data
   execution time: 2.23 hours; 2,032,569 records to process

            excl '' status  fixed parent-child  2017
as of:      Aug28       Aug31       Sep7        Oct7
taxon       1,925,615   1,933,060   1,878,575   1,425,833
occurrence  3,938,768   3,938,768   3,938,768   3,938,768
measurement 3,938,768   3,938,768   3,938,768   3,938,768

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IrmngAPI');



// $zip_path = DOC_ROOT . "../cp/IRMNG/IRMNG_DWC.zip";  //local MacMini
$zip_path = DOC_ROOT . "../other_files/IRMNG/IRMNG_DWC.zip";  //eol-archive

// gives the 503 Service Unavailable if hosted in eol-archive (editors.eol.org OR opendata.eol.org)
// $zip_path = "http://localhost/cp/IRMNG/IRMNG_DWC.zip";
// $zip_path = "https://opendata.eol.org/dataset/4e93fcec-fb78-4df9-be1b-90ac3f3a524a/resource/62e4bdfb-d6b5-4880-88ef-959195a1f638/download/irmngdwc.zip";
// $zip_path = "https://editors.eol.org/other_files/IRMNG/IRMNG_DWC.zip";
// $zip_path = "http://www.cmar.csiro.au/datacentre/downloads/IRMNG_DWC.zip"; //no longer available from partner


$timestart = time_elapsed();
$resource_id = 741;
$func = new IrmngAPI($resource_id, $zip_path);
// $func->get_taxa_without_status_but_with_eol_page(); //utility
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id, true, true);


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>