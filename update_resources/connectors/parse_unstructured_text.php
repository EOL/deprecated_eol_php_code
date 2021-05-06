<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
start of many iterations:
10088_5097	Tue 2021-04-13 01:26:39 PM	{"media_resource.tab":14220, "taxon.tab":13291, "time_elapsed":{"sec":1695.13, "min":28.25, "hr":0.47}}
10088_5097	Wed 2021-04-14 01:53:53 AM	{"media_resource.tab":14558, "taxon.tab":13606, "time_elapsed":{"sec":550.52, "min":9.18, "hr":0.15}}
10088_5097	Wed 2021-04-14 03:58:19 AM	{"media_resource.tab":12773, "taxon.tab":12084, "time_elapsed":{"sec":92.08, "min":1.53, "hr":0.03}}
10088_5097	Wed 2021-04-14 09:14:43 AM	{"media_resource.tab":12592, "taxon.tab":11935, "time_elapsed":{"sec":1255.52, "min":20.93, "hr":0.35}}
almost clean, first submission for review
10088_5097	Wed 2021-04-14 10:36:02 AM	{"media_resource.tab":12587, "taxon.tab":11930, "time_elapsed":{"sec":297.23, "min":4.95, "hr":0.08}}
10088_5097	Thu 2021-04-15 11:15:46 AM	{"media_resource.tab":12601, "taxon.tab":11936, "time_elapsed":{"sec":401.48, "min":6.69, "hr":0.11}}
10088_5097	Thu 2021-04-15 11:21:17 AM	{"media_resource.tab":12601, "taxon.tab":11936, "time_elapsed":{"sec":99.29, "min":1.65, "hr":0.03}}
10088_5097	Thu 2021-04-15 12:00:54 PM	{"media_resource.tab":12582, "taxon.tab":11919, "time_elapsed":{"sec":96.86, "min":1.61, "hr":0.03}}
10088_5097_ENV	Thu 2021-04-15 01:44:35 PM	{"measurement_or_fact_specific.tab":50022, "media_resource.tab":12582, "occurrence_specific.tab":50022, "taxon.tab":11919, "time_elapsed":{"sec":5788.24, "min":96.47, "hr":1.61}}
start where species sections with < 60 chars were removed: consistent, expected slightly decrease in all tables.
10088_5097	Sun 2021-04-18 01:30:45 PM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":362.79, "min":6.05, "hr":0.1}}
10088_5097_ENV	Sun 2021-04-18 01:42:34 PM	{"measurement_or_fact_specific.tab":49757, "media_resource.tab":12211, "occurrence_specific.tab":49757, "taxon.tab":11574, "time_elapsed":{"sec":704.52, "min":11.74, "hr":0.2}}
10088_5097	Mon 2021-04-19 09:17:13 AM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":405.85, "min":6.76, "hr":0.11}}
other adjustments:
10088_5097	Mon 2021-04-19 11:13:38 AM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":103.12, "min":1.72, "hr":0.03}}
10088_5097_ENV	Tue 2021-04-20 12:02:09 AM	{"measurement_or_fact_specific.tab":48153, "media_resource.tab":12211, "occurrence_specific.tab":48153, "taxon.tab":11574, "time_elapsed":{"sec":46101.88, "min":768.36, "hr":12.81}}
start here: remove any terms from the geographic ontology that include the string /ENVO_
10088_5097	Tue 2021-04-20 11:10:47 PM	{"media_resource.tab":12211, "taxon.tab":11574, "time_elapsed":{"sec":820.79, "min":13.68, "hr":0.23}}
10088_5097_ENV	Wed 2021-04-21 12:48:11 AM	{"measurement_or_fact_specific.tab":46711, "media_resource.tab":12211, "occurrence_specific.tab":46711, "taxon.tab":11574, "time_elapsed":{"sec":5834.72, "min":97.25, "hr":1.62}}
->expected decrease in MoF

10088_5097	Wed 2021-04-21 07:57:54 AM	{"association.tab":159, "media_resource.tab":12103, "occurrence.tab":170, "taxon.tab":11606, "time_elapsed":{"sec":109.99, "min":1.83, "hr":0.03}}
10088_5097_ENV	Wed 2021-04-21 08:47:34 AM	{"association.tab":159, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12103, "occurrence.tab":170, "occurrence_specific.tab":45913, "taxon.tab":11606, "time_elapsed":{"sec":103.1, "min":1.72, "hr":0.03}}
good status: 
10088_5097	Wed 2021-04-21 09:31:33 AM	{"association.tab":159, "media_resource.tab":12103, "occurrence.tab":170, "taxon.tab":11606, "time_elapsed":{"sec":284.58, "min":4.74, "hr":0.08}}
10088_5097_ENV	Wed 2021-04-21 09:33:26 AM	{"association.tab":159, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12103, "occurrence.tab":170, "occurrence_specific.tab":45913, "taxon.tab":11606, "time_elapsed":{"sec":106.64, "min":1.78, "hr":0.03}}
10088_5097	Wed 2021-04-21 12:31:24 PM	{"association.tab":157, "media_resource.tab":12100, "occurrence.tab":162, "taxon.tab":11596, "time_elapsed":{"sec":511.77, "min":8.53, "hr":0.14}}
10088_5097_ENV	Wed 2021-04-21 12:45:28 PM	{"association.tab":157, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12100, "occurrence.tab":162, "occurrence_specific.tab":45913, "taxon.tab":11596, "time_elapsed":{"sec":836, "min":13.93, "hr":0.23}}
start where "(as E. ramosus)" is removed: expected decrease in associations
10088_5097	Thu 2021-04-22 06:43:27 AM	{"association.tab":136, "media_resource.tab":12100, "occurrence.tab":145, "taxon.tab":11580, "time_elapsed":{"sec":498.54, "min":8.31, "hr":0.14}}
10088_5097_ENV	Thu 2021-04-22 06:53:24 AM	{"association.tab":136, "measurement_or_fact_specific.tab":45913, "media_resource.tab":12100, "occurrence_specific.tab":46058, "taxon.tab":11580, "time_elapsed":{"sec":592, "min":9.87, "hr":0.16}}
after fixing and started bringing in associations from e.g. HOST., and other adjustments.
10088_5097	Fri 2021-04-23 01:43:14 AM	{"association.tab":363, "media_resource.tab":12109, "occurrence.tab":428, "taxon.tab":11732, "time_elapsed":{"sec":246.78, "min":4.11, "hr":0.07}}
10088_5097_ENV	Fri 2021-04-23 02:14:12 AM	{"association.tab":363, "measurement_or_fact_specific.tab":45915, "media_resource.tab":12109, "occurrence_specific.tab":46343, "taxon.tab":11732, "time_elapsed":{"sec":1849.73, "min":30.83, "hr":0.51}}

10088_5097	Fri 2021-04-23 10:38:22 PM	{"association.tab":358, "media_resource.tab":12372, "occurrence.tab":422, "taxon.tab":11963, "time_elapsed":{"sec":544.41, "min":9.07, "hr":0.15}}
10088_5097_ENV	Fri 2021-04-23 10:49:30 PM	{"association.tab":358, "measurement_or_fact_specific.tab":45915, "media_resource.tab":12372, "occurrence_specific.tab":46337, "taxon.tab":11963, "time_elapsed":{"sec":661.79, "min":11.03, "hr":0.18}}
after some updates
10088_5097	Tue 2021-04-27 12:06:18 AM	{"association.tab":358, "media_resource.tab":12218, "occurrence.tab":422, "taxon.tab":11835, "time_elapsed":{"sec":261.58, "min":4.36, "hr":0.07}}
10088_5097_ENV	Tue 2021-04-27 01:42:33 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47476, "media_resource.tab":12218, "occurrence_specific.tab":47898, "taxon.tab":11835, "time_elapsed":{"sec":5765.42, "min":96.09, "hr":1.6}}

10088_5097	Tue 2021-04-27 03:16:36 AM	{"association.tab":358, "media_resource.tab":12216, "occurrence.tab":422, "taxon.tab":11834, "time_elapsed":{"sec":777.37, "min":12.96, "hr":0.22}}
10088_5097_ENV	Tue 2021-04-27 03:30:23 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47474, "media_resource.tab":12216, "occurrence_specific.tab":47896, "taxon.tab":11834, "time_elapsed":{"sec":818.91, "min":13.65, "hr":0.23}}

10088_5097	Tue 2021-04-27 11:10:27 AM	{"association.tab":358, "media_resource.tab":12216, "occurrence.tab":422, "taxon.tab":11834, "time_elapsed":{"sec":783.36, "min":13.06, "hr":0.22}}
10088_5097_ENV	Tue 2021-04-27 11:22:32 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47436, "media_resource.tab":12216, "occurrence_specific.tab":47858, "taxon.tab":11834, "time_elapsed":{"sec":715.81, "min":11.93, "hr":0.2}}

10088_5097	Tue 2021-04-27 11:37:20 AM	{"association.tab":358, "media_resource.tab":12216, "occurrence.tab":422, "taxon.tab":11834, "time_elapsed":{"sec":443.47, "min":7.39, "hr":0.12}}
10088_5097_ENV	Tue 2021-04-27 11:44:58 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47436, "media_resource.tab":12216, "occurrence_specific.tab":47858, "taxon.tab":11834, "time_elapsed":{"sec":452.07, "min":7.53, "hr":0.13}}

list-type incorporated:
10088_5097	Wed 2021-04-28 08:04:40 AM	{"association.tab":358, "media_resource.tab":13188, "occurrence.tab":422, "taxon.tab":12795, "time_elapsed":{"sec":481.93, "min":8.03, "hr":0.13}}
10088_5097_ENV	Wed 2021-04-28 08:16:42 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47468, "media_resource.tab":13188, "occurrence_specific.tab":47890, "taxon.tab":12795, "time_elapsed":{"sec":711.2, "min":11.85, "hr":0.2}}

subject = #uses removed: BEST STATS FOR COMPARISON
10088_5097	Wed 2021-04-28 09:32:45 AM	{"association.tab":358, "media_resource.tab":13188, "occurrence.tab":422, "taxon.tab":12795, "time_elapsed":{"sec":482.53, "min":8.04, "hr":0.13}}
10088_5097_ENV	Wed 2021-04-28 09:40:11 AM	{"association.tab":358, "measurement_or_fact_specific.tab":47468, "media_resource.tab":12222, "occurrence_specific.tab":47890, "taxon.tab":12795, "time_elapsed":{"sec":435.51, "min":7.26, "hr":0.12}}

expected increase in Media text objects
10088_5097	Wed 2021-04-28 12:00:30 PM	{"association.tab":358, "media_resource.tab":13306, "occurrence.tab":422, "taxon.tab":12867, "time_elapsed":{"sec":670.12, "min":11.17, "hr":0.19}}
10088_5097_ENV	Wed 2021-04-28 12:12:55 PM	{"association.tab":358, "measurement_or_fact_specific.tab":47468, "media_resource.tab":12222, "occurrence_specific.tab":47890, "taxon.tab":12867, "time_elapsed":{"sec":734.34, "min":12.24, "hr":0.2}}

expected some big changes in MoF fromt this point:
10088_5097	Sun 2021-05-02 10:30:35 PM	{"association.tab":358, "media_resource.tab":13306, "occurrence.tab":422, "taxon.tab":12867, "time_elapsed":{"sec":520.91, "min":8.68, "hr":0.14}}
10088_5097_ENV	Sun 2021-05-02 10:51:34 PM	{"association.tab":358, "measurement_or_fact_specific.tab":49319, "media_resource.tab":12222, "occurrence_specific.tab":49741, "taxon.tab":12867, "time_elapsed":{"sec":1249.22, "min":20.82, "hr":0.35}}

10088_5097	Mon 2021-05-03 12:56:04 AM	{"association.tab":358, "media_resource.tab":13770, "occurrence.tab":422, "taxon.tab":14014, "time_elapsed":{"sec":5561.63, "min":92.69, "hr":1.54}}
10088_5097_ENV	Mon 2021-05-03 01:03:49 AM	{"association.tab":358, "measurement_or_fact_specific.tab":49055, "media_resource.tab":12258, "occurrence_specific.tab":49477, "taxon.tab":14014, "time_elapsed":{"sec":455.55, "min":7.59, "hr":0.13}}

some epubs got included, expected increase:
10088_5097	Tue 2021-05-04 11:38:37 AM	{"association.tab":415, "media_resource.tab":13859, "occurrence.tab":467, "taxon.tab":14140, "time_elapsed":{"sec":4140.64, "min":69.01, "hr":1.15}}
10088_5097_ENV	Wed 2021-05-05 03:00:45 PM	{"association.tab":415, "measurement_or_fact_specific.tab":49290, "media_resource.tab":12347, "occurrence_specific.tab":49757, "taxon.tab":14140, "time_elapsed":{"sec":45763.69, "min":762.73, "hr":12.71}}
last run so far:
10088_5097	Wed 2021-05-05 11:04:45 PM	{"association.tab":413, "media_resource.tab":13697, "occurrence.tab":465, "taxon.tab":14001, "time_elapsed":{"sec":950.83, "min":15.85, "hr":0.26}}
10088_5097_ENV	Thu 2021-05-06 12:00:58 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49419, "media_resource.tab":12185, "occurrence_specific.tab":49884, "taxon.tab":14001, "time_elapsed":{"sec":3362.21, "min":56.04, "hr":0.93}}

10088_5097	Thu 2021-05-06 04:47:50 AM	{"association.tab":413, "media_resource.tab":13709, "occurrence.tab":465, "taxon.tab":14011, "time_elapsed":{"sec":1530.71, "min":25.51, "hr":0.43}}
10088_5097_ENV	Thu 2021-05-06 05:40:09 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49266, "media_resource.tab":12185, "occurrence_specific.tab":49731, "taxon.tab":14011, "time_elapsed":{"sec":3132.47, "min":52.21, "hr":0.87}}

10088_5097	Thu 2021-05-06 09:20:29 AM	{"association.tab":413, "media_resource.tab":13697, "occurrence.tab":465, "taxon.tab":14001, "time_elapsed":{"sec":743.97, "min":12.4, "hr":0.21}}
10088_5097_ENV	Thu 2021-05-06 09:30:32 AM	{"association.tab":413, "measurement_or_fact_specific.tab":49283, "media_resource.tab":12185, "occurrence_specific.tab":49748, "taxon.tab":14001, "time_elapsed":{"sec":593.88, "min":9.9, "hr":0.16}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ParseListTypeAPI');
require_library('connectors/ParseUnstructuredTextAPI');
$timestart = time_elapsed();
$func = new ParseUnstructuredTextAPI();

// $str = "Cambarus (Lacunicambarus) acanthura";
// // $str = "Cambarus Ruiz";
// // $str = "Cambarus morhua Ruiz";
// 
// // is_valid_species()
// $words = explode(" ", $str); 
// if(!@$words[1]) echo "\nfalse"; //No 2nd word
// else {
//     if(ctype_upper(substr($words[1],0,1))) echo "\nfalse"; //2nd word is capitalized
//     else echo("\ntrue");
// }
// exit("\n");

/* test
$str = "eli is 123 but cha is 23 and isaiah is 3";
if(preg_match_all('/\d+/', $str, $a)) //print_r($a[0]);
{
    $arr = $a[0];
    print_r($arr);
    foreach($arr as $num) {

        echo "\n$num is ".strlen($num)." digit(s)\n";
        
    }
}
exit("\n-end test-\n");
*/

/* parsing result of PdfParser
$filename = 'pdf2text_output.txt';
$func->parse_text_file($filename);
*/
/* parsing result pf pdftotext (legacy xpdf in EOL codebase)
$filename = 'SCtZ-0293-Hi_res.txt';
$func->parse_pdftotext_result($filename);
*/
/* parsing
$filename = 'pdf2text_output.txt';
$func->parse_pdftotext_result($filename);
*/
/* parsing SCtZ-0293-Hi_res.html
$filename = 'SCtZ-0293-Hi_res.html';
$func->parse_pdf2htmlEX_result($filename);
*/
/* parsing SCZ637_pdftotext.txt
$filename = 'SCZ637_pdftotext.txt';
$func->parse_pdftotext_result($filename);
*/

/* Start epub series: process our first file from the ticket */
$input = array('filename' => 'SCtZ-0293.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0293/');
$input = array('filename' => 'SCtZ-0001.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0001/');
$input = array('filename' => 'SCtZ-0008.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0008/');
$input = array('filename' => 'SCtZ-0016.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/');
$input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
$input = array('filename' => 'SCtZ-0011.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');

$input = array('filename' => 'SCTZ-0128.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0128/');
$input = array('filename' => 'SCtZ-0095.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0095/');
$input = array('filename' => 'SCtZ-0557.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0557/');
$input = array('filename' => 'SCtZ-0140.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0140/');
$input = array('filename' => 'SCTZ-0105.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0105/');
$input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
$input = array('filename' => 'SCtZ-0272.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0272/');
$input = array('filename' => 'SCtZ-0439.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0439/');
$input = array('filename' => 'SCTZ-0156.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0156/');
$input = array('filename' => 'SCtZ-0604.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0604/');
// -> 0604 I considered a regular species-type not a list-type

//start google sheet
$input = array('filename' => 'SCtZ-0004.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0004/');
$input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
$input = array('filename' => 'scz-0630.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/scz-0630/');
$input = array('filename' => 'SCtZ-0029.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0029/');
$input = array('filename' => 'SCtZ-0023.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0023/');
$input = array('filename' => 'SCtZ-0042.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0042/');
$input = array('filename' => 'SCtZ-0020.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0020/');
$input = array('filename' => 'SCtZ-0016.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/');
$input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
$input = array('filename' => 'SCtZ-0022.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0022/');
//May 4, 2021 Tue
$input = array('filename' => 'SCtZ-0019.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0019/');
$input = array('filename' => 'SCtZ-0002.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0002/');
$input = array('filename' => 'SCtZ-0017.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0017/');
$input = array('filename' => 'SCtZ-0009.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0009/');
//-> SCtZ-0009 no data, Has vernacular data for a good number of species though
$input = array('filename' => 'SCtZ-0003.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0003/');
$input = array('filename' => 'SCtZ-0616.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0616/');
$input = array('filename' => 'SCtZ-0617.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0617/');
$input = array('filename' => 'SCtZ-0615.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0615/');
//-> negative test, should not get any data
$input = array('filename' => 'SCtZ-0614.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/');
//-> has associations, species sections, no lists
$input = array('filename' => 'SCtZ-0612.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0612/');
$input = array('filename' => 'SCtZ-0605.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0605/');
// May 5, 2021 Wed
$input = array('filename' => 'SCtZ-0607.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0607/');
$input = array('filename' => 'SCtZ-0608.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0608/');
$input = array('filename' => 'SCtZ-0606.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0606/');
$input = array('filename' => 'SCtZ-0602.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0602/');
$input = array('filename' => 'SCtZ-0603.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0603/');
$input = array('filename' => 'SCtZ-0601.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0601/');
// // -> negative example, indeed no records created
$input = array('filename' => 'SCtZ-0598.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0598/');
$input = array('filename' => 'SCtZ-0594.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0594/');
    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0594/SCtZ-0594.txt

// May 6, 2021 Thu - 2nd repo

    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_6943/scb-0002/scb-0002.txt
    
    
    

// /* ---------------------------------- List-type here:
// variable lines_before_and_after_sciname is important. It is the lines before and after the "list header".

$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');
//-> good list data, no species sections

$input = array('filename' => 'SCtZ-0437.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/'); //List of Freshwater Fishes of Peru
//-> good list data, very bad species sections

// $input = array('filename' => 'SCtZ-0033.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/');
//-> good list data, a list-type with genus in one line and species in 2nd line. No species sections

$input = array('filename' => 'SCtZ-0018.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/');
//-> a list-type with genus in one line and species in 2nd line BUT no traits detected by Pensoft AND ALSO has good species sections

// $input = array('filename' => 'SCtZ-0010.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0010/');
// $input = array('filename' => 'SCtZ-0611.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0611/');
// $input = array('filename' => 'SCtZ-0613.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0613/');
//-> has good many species sections
// $input = array('filename' => 'SCtZ-0609.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0609/');

// May 6, 2021 Thu - 2nd repo
$input = array('filename' => 'scb-0002.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/');


$pdf_id = pathinfo($input['filename'], PATHINFO_FILENAME);
$input['lines_before_and_after_sciname'] = 2; //default
if(in_array($pdf_id, array('SCtZ-0010', 'SCtZ-0611', 'SCtZ-0613', 'scb-0002'))) $input['lines_before_and_after_sciname'] = 1;

if(Functions::is_production()) $input['epub_output_txts_dir'] = str_replace("/Volumes/AKiTiO4/other_files/Smithsonian/", "/extra/other_files/Smithsonian/", $input['epub_output_txts_dir']);
// ---------------------------------- */

$func->parse_pdftotext_result($input);

/* a utility
$func->utility_download_txt_files();
*/

/*
Real misfiled:
1. Taxonomy, sexual dimorphism, vertical distribution, and evolutionary zoogeography of the bathypelagic fish genus Stomias (Stomiatidae)
SCtZ-0031
2. Ten Rhyparus from the Western Hemisphere (Coleoptera: Scarabaeidae: Aphodiinae)	
SCtZ-0021
3.Gammaridean Amphipoda of Australia, Part III. The Phoxocephalidae
Gammaridean Amphipoda of Australia, Part I
SCtZ-0103
4.The Caridean shrimps (Crustacea:Decapoda) of the Albatross Philippine Expedition, 1907-1910, Part 7: Families Atyidae, Eugonatonotidae, Rhynchocinetidae, Bathypalaemonidae, Processidae, and Hippolytidae
The Caridean Shrimps (Crustacea: Decapoda) of the Albatross Philippine Expedition, 1907–1910, Part 5: Family Alpheidae
SCTZ-0466

wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437.txt
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>