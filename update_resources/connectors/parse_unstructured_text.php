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

/* test
$arr[] = 'aa';
$arr[] = 'bb';
print_r($arr);
$arr[] = 'cc';
$arr[] = 'dd';
$arr[] = 'ee';
print_r($arr);
array_shift($arr);
print_r($arr);
$arr[] = 'ff';
print_r($arr);
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

// /* Start epub series: process our first file from the ticket
$input = array('filename' => 'SCtZ-0293.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0293/');
$input = array('filename' => 'SCtZ-0001.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0001/');
$input = array('filename' => 'SCtZ-0008.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0008/');
$input = array('filename' => 'SCtZ-0016.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0016/');
$input = array('filename' => 'SCtZ-0029.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0029/');
$input = array('filename' => 'SCtZ-0023.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0023/');
$input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
// $input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
// $input = array('filename' => 'SCtZ-0011.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');
// $input = array('filename' => 'SCtZ-0003.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0003/');


$input = array('filename' => 'SCtZ-0025.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0025/');
$input = array('filename' => 'SCTZ-0128.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0128/');
$input = array('filename' => 'SCtZ-0095.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0095/');
$input = array('filename' => 'SCtZ-0557.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0557/');
$input = array('filename' => 'SCtZ-0140.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0140/');

    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0140/SCtZ-0140.txt


//List-type here:
/* var lines_before_and_after_sciname is important. It is the lines before and after the "list header". */
/*
$input = array('filename' => 'SCtZ-0033.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/');
$input = array('filename' => 'SCtZ-0437.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/'); //List of Freshwater Fishes of Peru
$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');

// $input = array('filename' => 'SCtZ-0018.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/');
//-> this has genus in one line and species in 2nd line

$input['lines_before_and_after_sciname'] = 2;
if($input['filename'] == 'SCtZ-0018.txt') $input['lines_before_and_after_sciname'] = 1;

if(Functions::is_production()) $input['epub_output_txts_dir'] = str_replace("/Volumes/AKiTiO4/other_files/Smithsonian/", "/extra/other_files/Smithsonian/", $input['epub_output_txts_dir']);
*/
$func->parse_pdftotext_result($input);
// */

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
/*

*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>