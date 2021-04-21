<?php
namespace php_active_record;
/*
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097_ENV.tar.gz
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "10088_5097"; //Smithsonian Contributions to Zoology --> first repository to process
// $resource_id = "10088_6943"; //Smithsonian Contributions to Botany -- 2nd repo to process

// /* un-comment in real operation - main operation
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
// */

/* during dev: processing associations
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->initialize();
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_tagged.txt";  $pdf_id = "SCtZ-0614";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0439/SCtZ-0439_tagged.txt";  $pdf_id = "SCtZ-0439";

$func->process_a_txt_file($txt_filename, $pdf_id, array());
$func->archive_builder_finalize();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
*/


/* utility --- copied template
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
// $func->check_unique_ids($resource_id); //takes time
$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id); // remove working dir
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>