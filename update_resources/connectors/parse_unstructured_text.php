<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ParseListTypeAPI');
require_library('connectors/ParseUnstructuredTextAPI');
$timestart = time_elapsed();
$func = new ParseUnstructuredTextAPI();

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

    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCTZ-0128/SCTZ-0128.txt


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