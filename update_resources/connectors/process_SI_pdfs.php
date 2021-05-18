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

/*
$str = "Capitophorus ohioensis Smith, 1940:141 [type: apt.v.f., Columbus, Ohio, 15–X–1938, CFS, on Helianthus; in USNM].";
echo "\n[$str]\n";
$str = trim(preg_replace('/\s*\[[^)]*\]/', '', $str)); //remove brackets
exit("\n[$str]\n");
*/

/* just test
$string = "HOST PLANTS.—Aster adnatus, A. asteroides (as Sericocarpus asteroides), A. carolinianus (Benjamin, 1934:37), A. concolor, Chrysopsisgraminifolia (as C. microcephala), C. latifolia, C. oligantha, Erigeron canadensis (as E. pusillus), E. strigosus (as E. ramosus), E. nudicaulis (as E. vernus), Heracleum sp. (Phillips, 1946:52), Hieracium argyreaeum, H. Gronovii, H. scabrum, H. venosum, H. sp., Prenanthes trifoliata, Trilisa paniculata, Sericocarpus acutisquamosus.";
echo "\n$string\n";
$string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
echo "\n$string\n";
exit("\n");
*/

// /* un-comment in real operation - main operation
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
// */

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

/* ========================== during dev: processing LIST-TYPE ==========================
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/SCtZ-0011_descriptions_LT.txt";  $pdf_id = "SCtZ-0011";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437_descriptions_LT.txt";  $pdf_id = "SCtZ-0437";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/SCtZ-0033_descriptions_LT.txt";  $pdf_id = "SCtZ-0033";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0010/SCtZ-0010_descriptions_LT.txt";  $pdf_id = "SCtZ-0010";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0611/SCtZ-0611_descriptions_LT.txt";  $pdf_id = "SCtZ-0611";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0613/SCtZ-0613_descriptions_LT.txt";  $pdf_id = "SCtZ-0613";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0609/SCtZ-0609_descriptions_LT.txt";  $pdf_id = "SCtZ-0609";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0604/SCtZ-0604_descriptions_LT.txt";  $pdf_id = "SCtZ-0604";

$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0004/SCtZ-0004_tagged.txt";  $pdf_id = "SCtZ-0004";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/scz-0630/scz-0630_descriptions_LT.txt";  $pdf_id = "scz-0630";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0029/SCtZ-0029_descriptions_LT.txt";  $pdf_id = "SCtZ-0029";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0023/SCtZ-0023_descriptions_LT.txt";  $pdf_id = "SCtZ-0023";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0042/SCtZ-0042_descriptions_LT.txt";  $pdf_id = "SCtZ-0042";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0020/SCtZ-0020_descriptions_LT.txt";  $pdf_id = "SCtZ-0020";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/SCtZ-0016_descriptions_LT.txt";  $pdf_id = "SCtZ-0016";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/SCtZ-0025_descriptions_LT.txt";  $pdf_id = "SCtZ-0025";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0022/SCtZ-0022_descriptions_LT.txt";  $pdf_id = "SCtZ-0022";
// May 4, 2021
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0019/SCtZ-0019_descriptions_LT.txt";  $pdf_id = "SCtZ-0019";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0002/SCtZ-0002_descriptions_LT.txt";  $pdf_id = "SCtZ-0002";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0017/SCtZ-0017_descriptions_LT.txt";  $pdf_id = "SCtZ-0017";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0003/SCtZ-0003_descriptions_LT.txt";  $pdf_id = "SCtZ-0003";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0616/SCtZ-0616_descriptions_LT.txt";  $pdf_id = "SCtZ-0616";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0617/SCtZ-0617_descriptions_LT.txt";  $pdf_id = "SCtZ-0617";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0615/SCtZ-0615_descriptions_LT.txt";  $pdf_id = "SCtZ-0615";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_descriptions_LT.txt";  $pdf_id = "SCtZ-0614";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0612/SCtZ-0612_descriptions_LT.txt";  $pdf_id = "SCtZ-0612";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0605/SCtZ-0605_descriptions_LT.txt";  $pdf_id = "SCtZ-0605";
// May 5, 2021
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0607/SCtZ-0607_descriptions_LT.txt";  $pdf_id = "SCtZ-0607";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0608/SCtZ-0608_descriptions_LT.txt";  $pdf_id = "SCtZ-0608";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0606/SCtZ-0606_descriptions_LT.txt";  $pdf_id = "SCtZ-0606";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0602/SCtZ-0602_descriptions_LT.txt";  $pdf_id = "SCtZ-0602";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0603/SCtZ-0603_descriptions_LT.txt";  $pdf_id = "SCtZ-0603";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0598/SCtZ-0598_descriptions_LT.txt";  $pdf_id = "SCtZ-0598";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0594/SCtZ-0594_descriptions_LT.txt";  $pdf_id = "SCtZ-0594";

$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0601/SCtZ-0601_descriptions_LT.txt";  $pdf_id = "SCtZ-0601";
// May 6, 2021 Thu
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/scb-0002_descriptions_LT.txt";  $pdf_id = "scb-0002";
//weird names found by Jen
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0188/SCtZ-0188_descriptions_LT.txt";  $pdf_id = "SCtZ-0188";
// May 10, 2021
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0003/scb-0003_descriptions_LT.txt";  $pdf_id = "scb-0003";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0004/scb-0004_descriptions_LT.txt";  $pdf_id = "scb-0004";
// May 13, 2021
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0007/scb-0007_descriptions_LT.txt";  $pdf_id = "scb-0007";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0009/scb-0009_descriptions_LT.txt";  $pdf_id = "scb-0009";
// May 17 Mon
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/SCtZ-0018_descriptions_LT.txt";  $pdf_id = "SCtZ-0018";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0032/SCtZ-0032_descriptions_LT.txt";  $pdf_id = "SCtZ-0032";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0034/SCtZ-0034_descriptions_LT.txt";  $pdf_id = "SCtZ-0034";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0113/SCtZ-0113_descriptions_LT.txt";  $pdf_id = "SCtZ-0113";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/SCtZ-0007_descriptions_LT.txt";  $pdf_id = "SCtZ-0007";

$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_descriptions_LT.txt";  $pdf_id = "SCtZ-0614";
// May 18 Tue
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0001/scb-0001_descriptions_LT.txt";  $pdf_id = "scb-0001";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/scb-0002_descriptions_LT.txt";  $pdf_id = "scb-0002";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0003/scb-0003_descriptions_LT.txt";  $pdf_id = "scb-0003";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0004/scb-0004_descriptions_LT.txt";  $pdf_id = "scb-0004";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0007/scb-0007_descriptions_LT.txt";  $pdf_id = "scb-0007";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0009/scb-0009_descriptions_LT.txt";  $pdf_id = "scb-0009";
// New May 18 Tue
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0013/scb-0013_descriptions_LT.txt";  $pdf_id = "scb-0013";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0027/scb-0027_descriptions_LT.txt";  $pdf_id = "scb-0027";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0094/scb-0094_descriptions_LT.txt";  $pdf_id = "scb-0094";


$resource_id = $pdf_id;
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->initialize();

//  utility - working OK
// $resource_id = "10088_5097";
// require_library('connectors/ParseListTypeAPI');
// require_library('connectors/SmithsonianPDFsAPI');
// $func = new SmithsonianPDFsAPI($resource_id);
// $func->clean_repository_of_old_files();
// exit;


if(file_exists($txt_filename)) $func->process_a_txt_file_LT($txt_filename, $pdf_id, array());

$txt_filename = str_replace("_descriptions_LT", "_tagged", $txt_filename);
if(file_exists($txt_filename)) $func->process_a_txt_file($txt_filename, $pdf_id, array());

$func->archive_builder_finalize();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
========================== end LIST-TYPE ==========================*/

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