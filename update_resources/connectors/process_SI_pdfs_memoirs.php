<?php
namespace php_active_record;
/*
https://repository.si.edu/handle/10088/5097         1st repo
https://repository.si.edu/handle/10088/6943         2nd repo
--------------------------------------------------
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097_ENV.tar.gz

wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_6943.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_6943_ENV.tar.gz
--------------------------------------------------
From local Mac mini: SCtZ-0614
10088_5097      {"association.tab":56, "media_resource.tab":10, "occurrence.tab":55, "taxon.tab":54}
10088_5097_ENV  {"association.tab":56, "measurement_or_fact_specific.tab":150, "media_resource.tab":10, "occurrence.tab":55, "occurrence_specific.tab":150, "taxon.tab":54}

10088_5097_ENV  {"association.tab":56, "measurement_or_fact_specific.tab":150, "media_resource.tab":10, "occurrence.tab":55, "occurrence_specific.tab":150, "taxon.tab":54, "time_elapsed":{"sec":17.41, "min":0.29, "hr":0}}
10088_5097_ENV  {"association.tab":56, "measurement_or_fact_specific.tab":150, "media_resource.tab":10, "occurrence_specific.tab":205, "taxon.tab":54, "time_elapsed":{"sec":18.34, "min":0.31, "hr":0.01}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS["ENV_DEBUG"] = true;
$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
/*
php5.6 process_SI_pdfs.php jenkins '{"resource_id": "10088_5097", "resource_name":"SI Contributions to Zoology"}'   //1st repo to process
php5.6 process_SI_pdfs.php jenkins '{"resource_id": "10088_6943", "resource_name":"SI Contributions to Botany"}'    //2nd repo

process_SI_pdfs.php _ '{"resource_id": "10088_5097", "resource_name":"SI Contributions to Zoology"}'
process_SI_pdfs.php _ '{"resource_id": "10088_6943", "resource_name":"SI Contributions to Botany"}'
*/

/* un-comment in real operation - main operation
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
*/

/* ========================== during dev: processing associations ==========================
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_tagged.txt";  $pdf_id = "SCtZ-0614";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0439/SCtZ-0439_tagged.txt";  $pdf_id = "SCtZ-0439";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0156/SCTZ-0156_tagged.txt";  $pdf_id = "SCTZ-0156";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0004/SCtZ-0004_tagged.txt";  $pdf_id = "SCtZ-0004";

$resource_id = $pdf_id;
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->initialize();

$func->process_a_txt_file($txt_filename, $pdf_id, array());
$func->archive_builder_finalize();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
========================== END ========================== */

// /* ========================== during dev: processing LIST-TYPE ==========================
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0605/SCtZ-0605_descriptions_LT.txt";  $pdf_id = "SCtZ-0605";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_descriptions_LT.txt";  $pdf_id = "SCtZ-0614";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0093/scb-0093_descriptions_LT.txt";  $pdf_id = "scb-0093";

$pdf_id = '118935'; //1st doc
$pdf_id = '120081'; //2nd doc

$resource_id = $pdf_id;
require_library('connectors/ParseListTypeAPI_Memoirs');
require_library('connectors/SmithsonianPDFsAPI_Memoirs');
$func = new SmithsonianPDFsAPI_Memoirs($resource_id);
$func->initialize();
$func->generate_dwca_for_a_repository(); //for all files in entire repo: "Memoirs of the American Entomological Society"

/* for single file, during dev
if(file_exists($txt_filename)) $func->process_a_txt_file_LT($txt_filename, $pdf_id, array());
$txt_filename = str_replace("_descriptions_LT", "_tagged", $txt_filename);
if(file_exists($txt_filename)) $func->process_a_txt_file($txt_filename, $pdf_id, array());
*/
$func->archive_builder_finalize();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
// ========================== end LIST-TYPE ==========================*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>