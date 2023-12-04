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
10088_5097      {"assoc.tab":56,                "media.tab":10, "occurrence.tab":55,                           "taxon.tab":54}
10088_5097_ENV  {"assoc.tab":56, "MoF.tab":150, "media.tab":10, "occurrence.tab":55, "occur_specific.tab":150, "taxon.tab":54}
10088_5097_ENV  {"assoc.tab":56, "MoF.tab":150, "media.tab":10, "occurrence.tab":55, "occur_specific.tab":150, "taxon.tab":54, "time_elapsed":{"sec":17.41, "min":0.29, "hr":0}}
10088_5097_ENV  {"assoc.tab":56, "MoF.tab":150, "media.tab":10,                      "occur_specific.tab":205, "taxon.tab":54, "time_elapsed":{"sec":18.34, "min":0.31, "hr":0.01}}

From eol-archive:
 ========== Repo 1: Smithsonian Contributions to Zoology ==========
10088_5097	    Tue 2021-05-18 08:10:09 AM	{"association.tab":365,                  "media.tab":12698, "occur.tab":418,            "taxon.tab":12187, "time_elapsed":{"sec":1363.06, "min":22.72, "hr":0.38}}
10088_5097_ENV	Tue 2021-05-18 08:19:00 AM	{"association.tab":365, "MoF.tab":47768, "media.tab":11453, "occur_specific.tab":48186, "taxon.tab":12187, "time_elapsed":{"sec":521.58, "min":8.69, "hr":0.14}}
after putting more stop patterns from spreadsheet
10088_5097	    Wed 2021-05-19 09:55:52 AM	{"association.tab":365,                  "media.tab":12677, "occur.tab":418,            "taxon.tab":12176, "time_elapsed":{"sec":4284.89, "min":71.41, "hr":1.19}}
10088_5097_ENV	Wed 2021-05-19 10:10:17 AM	{"association.tab":365, "MoF.tab":47207, "media.tab":11434, "occur_specific.tab":47625, "taxon.tab":12176, "time_elapsed":{"sec":856.04, "min":14.27, "hr":0.24}}
after a couple of months: ACCEPTABLE --- SAME MEDIA AND TAXON AND ASSOC
10088_5097	    Tue 2021-09-28 01:49:58 AM	{"association.tab":365,                  "media.tab":12677, "occur.tab":418,            "taxon.tab":12176, "time_elapsed":{"sec":1363.19, "min":22.72, "hr":0.38}}
10088_5097_ENV	Tue 2021-09-28 01:59:38 AM	{"association.tab":365, "MoF.tab":42655, "media.tab":11434, "occur_specific.tab":43073, "taxon.tab":12176, "time_elapsed":{"sec":573.31, "min":9.56, "hr":0.16}}
after a couple of weeks: EXACT, STILL SAME MEDIA AND TAXON - EXCELLENT!
10088_5097	    Wed 2021-10-06 04:52:20 AM	{"association.tab":365,                  "media.tab":12677, "occur.tab":418,            "taxon.tab":12176, "time_elapsed":{"sec":1310.6, "min":21.84, "hr":0.36}}
10088_5097_ENV	Wed 2021-10-06 05:01:09 AM	{"association.tab":365, "MoF.tab":42655, "media.tab":11434, "occur_specific.tab":43073, "taxon.tab":12176, "time_elapsed":{"sec":520.83, "min":8.68, "hr":0.14}}

with NEW host patterns: DATA-1891 --- with slight increase in associations n=3+1
10088_5097	    Thu 2021-10-07 02:28:35 AM	{"association.tab":368,                  "media.tab":12677, "occur.tab":423,            "taxon.tab":12179, "time_elapsed":{"sec":1186.61, "min":19.78, "hr":0.33}}
10088_5097_ENV	Thu 2021-10-07 02:37:14 AM	{"association.tab":368, "MoF.tab":42655, "media.tab":11434, "occur_specific.tab":43078, "taxon.tab":12179, "time_elapsed":{"sec":511.09, "min":8.52, "hr":0.14}}
after DATA-1893: new patterns:
10088_5097	    Wed 2021-10-13 04:13:01 AM	{"association.tab":369,                  "media.tab":12677, "occur.tab":424,            "taxon.tab":12179, "time_elapsed":{"sec":2856.84, "min":47.61, "hr":0.79}}
10088_5097_ENV	Wed 2021-10-13 04:36:34 AM	{"association.tab":369, "MoF.tab":42655, "media.tab":11434, "occur_specific.tab":43079, "taxon.tab":12179, "time_elapsed":{"sec":1403.87, "min":23.4, "hr":0.39}}
gnfinder
10088_5097	    Wed 2021-10-20 10:35:08 PM	{"association.tab":369,                  "media.tab":12683, "occur.tab":424,            "taxon.tab":12173, "time_elapsed":{"sec":45285.87, "min":754.76, "hr":12.58}}
10088_5097_ENV	Wed 2021-10-20 11:23:16 PM	{"association.tab":369, "MoF.tab":42686, "media.tab":11440, "occur_specific.tab":43110, "taxon.tab":12173, "time_elapsed":{"sec":2879.71, "min":48, "hr":0.8}}
relaxed name filter
10088_5097	    Thu 2021-10-21 12:06:43 PM	{"association.tab":369,                  "media.tab":12706, "occur.tab":424,            "taxon.tab":12211, "time_elapsed":{"sec":11976.1, "min":199.6, "hr":3.33}}
10088_5097_ENV	Thu 2021-10-21 12:32:33 PM	{"association.tab":369, "MoF.tab":42718, "media.tab":11462, "occur_specific.tab":43142, "taxon.tab":12211, "time_elapsed":{"sec":1540.46, "min":25.67, "hr":0.43}}
not relaxed
10088_5097	    Fri 2021-10-22 01:21:49 AM	{"association.tab":369,                  "media.tab":12706, "occur.tab":424,            "taxon.tab":12211, "time_elapsed":{"sec":2261.95, "min":37.7, "hr":0.63}}
10088_5097_ENV	Fri 2021-10-22 01:36:04 AM	{"association.tab":369, "MoF.tab":42718, "media.tab":11462, "occur_specific.tab":43142, "taxon.tab":12211, "time_elapsed":{"sec":845.03, "min":14.08, "hr":0.23}}
10088_5097	    Mon 2021-10-25 06:30:31 AM	{"association.tab":369,                  "media.tab":12705, "occur.tab":424,            "taxon.tab":12210, "time_elapsed":{"sec":2845.32, "min":47.42, "hr":0.79}}
10088_5097_ENV	Mon 2021-10-25 06:48:53 AM	{"association.tab":369, "MoF.tab":42718, "media.tab":11461, "occur_specific.tab":43142, "taxon.tab":12210, "time_elapsed":{"sec":1093.98, "min":18.23, "hr":0.3}}
10088_5097	    Mon 2021-10-25 10:46:28 AM	{"association.tab":369,                  "media.tab":12704, "occur.tab":424,            "taxon.tab":12209, "time_elapsed":{"sec":2778.54, "min":46.31, "hr":0.77}}
10088_5097_ENV	Mon 2021-10-25 11:09:52 AM	{"association.tab":369, "MoF.tab":42718, "media.tab":11461, "occur_specific.tab":43142, "taxon.tab":12209, "time_elapsed":{"sec":1398.33, "min":23.31, "hr":0.39}}
assoc true gnfinder
10088_5097	Tue 2021-10-26 11:15:57 PM	    {"association.tab":376,                  "media.tab":12705, "occur.tab":436,            "taxon.tab":12217, "time_elapsed":{"sec":4243.26, "min":70.72, "hr":1.18}}
10088_5097_ENV	Tue 2021-10-26 11:44:59 PM	{"association.tab":376, "MoF.tab":42743, "media.tab":11462, "occur_specific.tab":43179, "taxon.tab":12217, "time_elapsed":{"sec":1736.11, "min":28.94, "hr":0.48}}
10088_5097	    Wed 2021-10-27 06:45:21 AM	{"association.tab":376,                  "media.tab":12705, "occur.tab":436,            "taxon.tab":12217, "time_elapsed":{"sec":2879.81, "min":48, "hr":0.8}}
10088_5097_ENV	Wed 2021-10-27 07:06:32 AM	{"association.tab":376, "MoF.tab":42743, "media.tab":11462, "occur_specific.tab":43179, "taxon.tab":12217, "time_elapsed":{"sec":1263.63, "min":21.06, "hr":0.35}}
========== Repo 2: Smithsonian Contributions to Botany ==========
With growth ontology:
10088_6943	    Sat 2021-05-22 04:24:44 AM	{                "media.tab":1649,                            "taxon.tab":1549, "time_elapsed":{"sec":89.38, "min":1.49, "hr":0.02}}
10088_6943_ENV	Sat 2021-05-22 04:25:50 AM	{"MoF.tab":6300, "media.tab":1487, "occur_specific.tab":6300, "taxon.tab":1549, "time_elapsed":{"sec":57.82, "min":0.96, "hr":0.02}}
excluded 1 growth uri
10088_6943	    Tue 2021-06-01 01:22:50 AM	{                "media.tab":1649,                            "taxon.tab":1549, "time_elapsed":{"sec":104.04, "min":1.73, "hr":0.03}}
10088_6943_ENV	Tue 2021-06-01 01:23:38 AM	{"MoF.tab":6234, "media.tab":1487, "occur_specific.tab":6234, "taxon.tab":1549, "time_elapsed":{"sec":38.39, "min":0.64, "hr":0.01}}
after a couple of months: ACCEPTABLE --- SAME MEDIA AND TAXON
10088_6943	    Tue 2021-09-28 02:39:43 AM	{                "media.tab":1649,                            "taxon.tab":1549, "time_elapsed":{"sec":44.76, "min":0.75, "hr":0.01}}
10088_6943_ENV	Tue 2021-09-28 02:40:18 AM	{"MoF.tab":6012, "media.tab":1487, "occur_specific.tab":6012, "taxon.tab":1549, "time_elapsed":{"sec":27.87, "min":0.46, "hr":0.01}}
after a couple of weeks: EXACT, STILL SAME MEDIA AND TAXON - EXCELLENT!
10088_6943	    Wed 2021-10-06 04:32:10 AM	{                "media.tab":1649,                            "taxon.tab":1549, "time_elapsed":{"sec":97.26, "min":1.62, "hr":0.03}}
10088_6943_ENV	Wed 2021-10-06 04:33:20 AM	{"MoF.tab":6012, "media.tab":1487, "occur_specific.tab":6012, "taxon.tab":1549, "time_elapsed":{"sec":62.09, "min":1.03, "hr":0.02}}
with NEW host patterns: DATA-1891 --- NO CHANGE SO FAR
10088_6943	    Thu 2021-10-07 02:11:08 AM	{                "media.tab":1649,                            "taxon.tab":1549, "time_elapsed":{"sec":90.06, "min":1.5, "hr":0.03}}
10088_6943_ENV	Thu 2021-10-07 02:12:20 AM	{"MoF.tab":6012, "media.tab":1487, "occur_specific.tab":6012, "taxon.tab":1549, "time_elapsed":{"sec":62.6, "min":1.04, "hr":0.02}}
after DATA-1893: new patterns:
10088_6943	    Wed 2021-10-13 03:30:24 AM	{                "media.tab":1649,                            "taxon.tab":1549, "time_elapsed":{"sec":274.41, "min":4.57, "hr":0.08}}
10088_6943_ENV	Wed 2021-10-13 03:31:53 AM	{"MoF.tab":6012, "media.tab":1487, "occur_specific.tab":6012, "taxon.tab":1549, "time_elapsed":{"sec":83.48, "min":1.39, "hr":0.02}}
gnfinder
10088_6943	    Thu 2021-10-21 12:42:09 AM	{                "media.tab":1664,                            "taxon.tab":1561, "time_elapsed":{"sec":4702.44, "min":78.37, "hr":1.31}}
10088_6943_ENV	Thu 2021-10-21 12:45:23 AM	{"MoF.tab":6058, "media.tab":1501, "occur_specific.tab":6058, "taxon.tab":1561, "time_elapsed":{"sec":187.33, "min":3.12, "hr":0.05}}
relaxed name filter
10088_6943	    Thu 2021-10-21 12:51:49 PM	{                "media.tab":1667,                            "taxon.tab":1567, "time_elapsed":{"sec":1136.86, "min":18.95, "hr":0.32}}
10088_6943_ENV	Thu 2021-10-21 12:56:08 PM	{"MoF.tab":6057, "media.tab":1505, "occur_specific.tab":6057, "taxon.tab":1567, "time_elapsed":{"sec":250.88, "min":4.18, "hr":0.07}}
not relaxed
10088_6943	    Fri 2021-10-22 01:38:11 AM	{                "media.tab":1667,                            "taxon.tab":1567, "time_elapsed":{"sec":116.61, "min":1.94, "hr":0.03}}
10088_6943_ENV	Fri 2021-10-22 01:39:06 AM	{"MoF.tab":6057, "media.tab":1505, "occur_specific.tab":6057, "taxon.tab":1567, "time_elapsed":{"sec":47.33, "min":0.79, "hr":0.01}}
10088_6943	    Mon 2021-10-25 11:13:22 AM	{                "media.tab":1667,                            "taxon.tab":1567, "time_elapsed":{"sec":197.31, "min":3.29, "hr":0.05}}
10088_6943_ENV	Mon 2021-10-25 11:14:55 AM	{"MoF.tab":6057, "media.tab":1505, "occur_specific.tab":6057, "taxon.tab":1567, "time_elapsed":{"sec":85.96, "min":1.43, "hr":0.02}}
assoc true gnfinder
10088_6943	    Tue 2021-10-26 11:48:46 PM	{                "media.tab":1667,                            "taxon.tab":1567, "time_elapsed":{"sec":218.07, "min":3.63, "hr":0.06}}
10088_6943_ENV	Tue 2021-10-26 11:50:15 PM	{"MoF.tab":6057, "media.tab":1505, "occur_specific.tab":6057, "taxon.tab":1567, "time_elapsed":{"sec":81.67, "min":1.36, "hr":0.02}}

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
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

// /* un-comment in real operation - MAIN OPERATION - this generates the whole repository. Processes many docs per repository.
require_library('connectors/Functions_Memoirs');
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder. here ditox Dec 4, 2023
// */

/* BELOW HERE IS WHEN YOU WANT TO PROCESS ONE FILE AT A TIME. DURING DEV. 
used during dev (one file at a time using param $txt_filename):
php update_resources/connectors/process_SI_pdfs.php 
-> runs after parse_unstructured_text.php is run.
*/
/* ========================== during dev: processing associations ========================== here ditox Dec 4, 2023
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_tagged.txt";  $pdf_id = "SCtZ-0614";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0439/SCtZ-0439_tagged.txt";  $pdf_id = "SCtZ-0439";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0156/SCTZ-0156_tagged.txt";  $pdf_id = "SCTZ-0156";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0004/SCtZ-0004_tagged.txt";  $pdf_id = "SCtZ-0004";

$resource_id = $pdf_id;
require_library('connectors/Functions_Memoirs');
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->initialize();

$func->process_a_txt_file($txt_filename, $pdf_id, array());
$func->archive_builder_finalize();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
========================== END: processing associations ========================== */

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
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0001/scb-0001_descriptions_LT.txt";  $pdf_id = "scb-0001";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/scb-0002_descriptions_LT.txt";  $pdf_id = "scb-0002";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0003/scb-0003_descriptions_LT.txt";  $pdf_id = "scb-0003";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0004/scb-0004_descriptions_LT.txt";  $pdf_id = "scb-0004";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0007/scb-0007_descriptions_LT.txt";  $pdf_id = "scb-0007";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0009/scb-0009_descriptions_LT.txt";  $pdf_id = "scb-0009";
// New May 18 Tue
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0013/scb-0013_descriptions_LT.txt";  $pdf_id = "scb-0013";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0027/scb-0027_descriptions_LT.txt";  $pdf_id = "scb-0027";
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0094/scb-0094_descriptions_LT.txt";  $pdf_id = "scb-0094";
// May 19 Wed
// $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0093/scb-0093_descriptions_LT.txt";  $pdf_id = "scb-0093";

// ============== put in here the item you wish to run:
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/scb-0002_descriptions_LT.txt";  $pdf_id = "scb-0002";
$txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0611/SCtZ-0611_descriptions_LT.txt";  $pdf_id = "SCtZ-0611";
// ==============

$resource_id = $pdf_id;
require_library('connectors/Functions_Memoirs');
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