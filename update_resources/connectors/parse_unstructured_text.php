<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
start of many iterations:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS['ENV_DEBUG'] = false;
require_library('connectors/Functions_Memoirs');
require_library('connectors/ParseListTypeAPI');
require_library('connectors/ParseUnstructuredTextAPI');
$timestart = time_elapsed();
$func = new ParseUnstructuredTextAPI();

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
/* Start epub series: process our first file from the ticket 
used during dev (one file at a time using param $input): php update_resources/connectors/parse_unstructured_text.php
*/
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
    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0061/SCtZ-0061.txt

//fix weird names found by Jen:
$input = array('filename' => 'SCtZ-0355.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0355/');
$input = array('filename' => 'SCtZ-0188.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0188/');
$input = array('filename' => 'SCtZ-0559.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0559/');
$input = array('filename' => 'SCtZ-0061.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0061/');


// May 6, 2021 Thu - 2nd repo
// $input = array('filename' => 'scb-0001.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0001/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 43
// http://purl.org/dc/dcmitype/Text: 52
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 174

// May 10, 2021 - 2nd repo
// $input = array('filename' => 'scb-0003.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0003/');
// -> Jen considered as list but not really. Better to acquire is as regular species-sections type
// http://rs.tdwg.org/dwc/terms/taxon: Total: 98
// http://purl.org/dc/dcmitype/Text: 98
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 99

// $input = array('filename' => 'scb-0004.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0004/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 23
// http://purl.org/dc/dcmitype/Text: 23
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 152

// May 13, 2021 Thu
// $input = array('filename' => 'scb-0006.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0006/');
// -> no records created, skipped for the meantime
// -> I don't think it's worth accomodating this case unless it turns out to be common.
// $input = array('filename' => 'scb-0005.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0005/');
// -> as expected, didn't create any records

// $input = array('filename' => 'scb-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0007/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 3
// http://purl.org/dc/dcmitype/Text: 3
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 74

// $input = array('filename' => 'scb-0009.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0009/');
// http://rs.tdwg.org/dwc/terms/taxon: Total: 10
// http://purl.org/dc/dcmitype/Text: 10
// http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 28



// May 17 Mon
// $input = array('filename' => 'SCtZ-0032.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0032/');
// -> 7 scinames
// $input = array('filename' => 'SCtZ-0034.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0034/');
// -> 103 scinames
// $input = array('filename' => 'SCtZ-0062.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0062/');
// -> 10 scinames
// $input = array('filename' => 'SCtZ-0067.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0067/');
// -> 16 scinames
// $input = array('filename' => 'SCtZ-0063.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0063/');
// -> 11 scinames
// $input = array('filename' => 'SCtZ-0113.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0113/');
// -> 91 scinames
// $input = array('filename' => 'SCtZ-0007.txt', 'lines_before_and_after_sciname' => 1, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0007/');
// -> 19 scinames
$input = array('filename' => 'SCTZ-0275.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0275/');
// -> 4 scinames
// $input = array('filename' => 'SCTZ-0469.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0469/');
// -> 23 scinames
// $input = array('filename' => 'SCtZ-0006.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0006/');
// -> 6 scinames

$input = array('filename' => 'SCtZ-0614.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/');
//-> has associations (57), species sections (9), no lists
// resources/SCtZ-0614/association.tab]    :: total: [57]
// resources/SCtZ-0614/media_resource.tab] :: total: [9]
// resources/SCtZ-0614/occurrence.tab]     :: total: [57]
// resources/SCtZ-0614/taxon.tab]          :: total: [53]

// New - May 18 Tue    
// $input = array('filename' => 'scb-0013.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0013/');
// -> 11 scinames
// $input = array('filename' => 'scb-0027.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0027/');
// -> 131 scinames
// $input = array('filename' => 'scb-0094.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0094/');
// -> 116 scinames

// May 19 Wed
// $input = array('filename' => 'SCtZ-0031.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0031/');
// -> none

// $input = array('filename' => 'scb-0092.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0092/');
// -> none
// $input = array('filename' => 'scb-0093.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0093/');
// -> 34 scinames

// $input = array('filename' => 'SCtZ-0084.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0084/');
// -> 27 scinames

// $input = array('filename' => 'SCtZ-0107.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0107/');

// wget https://editors.eol.org/other_files/Smithsonian/epub_10088_6943/scb-0092/scb-0092.txt


/* ---------------------------------- List-type here:
// variable lines_before_and_after_sciname is important. It is the lines before and after the "list header".

$input = array('filename' => 'SCtZ-0011.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0011/');
//-> good list data, no species sections

$input = array('filename' => 'SCtZ-0437.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/'); //List of Freshwater Fishes of Peru
//-> good list data, very bad species sections

$input = array('filename' => 'SCtZ-0033.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0033/');
//-> good list data, a list-type with genus in one line and species in 2nd line. No species sections

// $input = array('filename' => 'SCtZ-0010.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0010/');
// $input = array('filename' => 'SCtZ-0611.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0611/');
$input = array('filename' => 'SCtZ-0613.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0613/');
//-> has good many species sections
// $input = array('filename' => 'SCtZ-0609.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0609/');
// -> 60 rows

// May 6, 2021 Thu - 2nd repo
$input = array('filename' => 'scb-0002.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/');
// -> http://rs.tdwg.org/dwc/terms/taxon:             Total: 162
// -> http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 165

// May 17 Mon - 1st repo
// $input = array('filename' => 'SCtZ-0018.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0018/');
//-> a list-type with genus in one line and species in 2nd line BUT no traits detected by Pensoft AND ALSO has good species sections
// -> 38 scinames


    // wget https://editors.eol.org/other_files/Smithsonian/epub_10088_6943/scb-0003/scb-0003.txt

---------------------------------- */

// /* when testing copy desired $input here, then run: php update_resources/connectors/parse_unstructured_text.php
$input = array('filename' => 'SCTZ-0156.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCTZ-0156/');
// $input = array('filename' => 'SCtZ-0004.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0004/');
$input = array('filename' => 'scb-0002.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/scb-0002/');
$input = array('filename' => 'SCtZ-0611.txt', 'type' => 'list', 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0611/');
$input = array('filename' => 'SCtZ-0614.txt', 'lines_before_and_after_sciname' => 2, 'epub_output_txts_dir' => '/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/');
// */

$pdf_id = pathinfo($input['filename'], PATHINFO_FILENAME);
$input['lines_before_and_after_sciname'] = 2; //default
if(in_array($pdf_id, array('SCtZ-0007', 'SCtZ-0025', 'SCtZ-0020', 'SCtZ-0019', 'SCtZ-0011', 'SCtZ-0010', 'SCtZ-0611', 'SCtZ-0613',
    'scb-0001', 'scb-0002', 'scb-0003', 'scb-0006', 'scb-0004', 'scb-0007'))) $input['lines_before_and_after_sciname'] = 1;

if(Functions::is_production()) $input['epub_output_txts_dir'] = str_replace("/Volumes/AKiTiO4/other_files/Smithsonian/", "/extra/other_files/Smithsonian/", $input['epub_output_txts_dir']);

// /*

if(stripos($input['epub_output_txts_dir'], "epub_10088_5097") !== false) $folder = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/$pdf_id/";
if(stripos($input['epub_output_txts_dir'], "epub_10088_6943") !== false) $folder = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_6943/$pdf_id/";

$postfix = array("_tagged.txt", "_tagged_LT.txt", "_edited.txt", "_edited_LT.txt", "_descriptions_LT.txt");
foreach($postfix as $post) {
    $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."$post";
    $txt_filename = $folder."/".$txt_filename;
    echo "\n$txt_filename - ";
    if(file_exists($txt_filename)) if(unlink($txt_filename)) echo " deleted OK\n";
    else                                                     echo " does not exist\n";
}
// exit("\n-end for now-\n");
// */

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