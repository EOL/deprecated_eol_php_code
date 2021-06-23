<?php
namespace php_active_record;
/* DATA-1877: textmining more unstructured text
118935	Mon 2021-06-21 10:33:30 AM	    {                "media_resource.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":3.64, "min":0.06, "hr":0}}
118935_ENV	Mon 2021-06-21 10:42:29 AM	{"MoF.tab":1448, "media_resource.tab":1309, "occur.tab":1448, "taxon.tab":1308, "time_elapsed":{"sec":490.28, "min":8.17, "hr":0.14}}
118935	Tue 2021-06-22 12:37:41 AM	    {                "media_resource.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.3, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-06-22 12:40:29 AM	{"MoF.tab":1448, "media_resource.tab":1309, "occur.tab":1448, "taxon.tab":1308, "time_elapsed":{"sec":167.64, "min":2.79, "hr":0.05}}
118935	Tue 2021-06-22 01:07:09 AM	    {                "media_resource.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.38, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-06-22 01:08:55 AM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":105.96, "min":1.77, "hr":0.03}}
118935	Tue 2021-06-22 12:32:41 PM	    {                "media_resource.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.11, "min":0.02, "hr":0}}
118935_ENV	Tue 2021-06-22 12:32:57 PM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":16.69, "min":0.28, "hr":0}}

120081	Tue 2021-06-22 12:43:28 PM	    {               "media_resource.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.92, "min":0.02, "hr":0}}
120081_ENV	Tue 2021-06-22 12:48:10 PM	{"MoF.tab":519, "media_resource.tab":95, "occur.tab":519, "taxon.tab":95, "time_elapsed":{"sec":280.95, "min":4.68, "hr":0.08}}
------------------------------------------------------------
php5.6 parse_unstructured_text_memoirs.php jenkins '{"resource_id": "118935", "resource_name":"1st doc"}'
php5.6 parse_unstructured_text_memoirs.php jenkins '{"resource_id": "120081", "resource_name":"2nd doc"}'

parse_unstructured_text_memoirs.php _ '{"resource_id": "118935", "resource_name":"1st doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120081", "resource_name":"2nd doc"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ParseListTypeAPI_Memoirs');
require_library('connectors/ParseUnstructuredTextAPI_Memoirs');
$timestart = time_elapsed();
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$pdf_id = $param['resource_id'];
$func = new ParseUnstructuredTextAPI_Memoirs();
/*
$row = "EZRA TOWNSEND CRESSON 2J";
$tmp = str_replace(array(" ",".",","), "", $row);
$tmp = preg_replace('/[0-9]+/', '', $tmp); //remove For Western Arabic numbers (0-9):
$tmp = trim($tmp);
if(ctype_upper($tmp)) echo "\nupper [$tmp]\n";  //entire row is upper case //EZRA TOWNSEND CRESSON
else echo "\nlower [$tmp]\n";
exit;
*/
/*
$string = "Pegomyia palposa (Stein) (Figs. 1, 30, 54.)";
$string = trim(preg_replace('/\s*\(Fig[^)]*\)/', '', $string)); //remove parenthesis OK
[Pegomyia palposa (Stein)]
echo "\n[$string]\n";
exit("\n");
*/
/*
$str = "the quick <k brown fox";
$pos = strpos($str, "<");
$substr = substr($str, $pos, 2);
echo "\n[$str]\n[$pos]\n[$substr]\n";
$str = str_replace($substr, "", $str);
$str = Functions::remove_whitespace($str);
echo "\n[$str]\n";
exit("\n-end-\n");
*/
$rec[118935] = array('filename' => '118935.txt', 'lines_before_and_after_sciname' => 1);
    /* stable stats:
    blocks: 1322
    Raw scinames count: 1322
    */
$rec[120081] = array('filename' => '120081.txt', 'lines_before_and_after_sciname' => 2);
    /* stable stats:
    blocks: 97
    Raw scinames count: 98
    */
if($val = @$rec[$pdf_id]) $input = $val;
else exit("\nUndefined PDF ID\n");
/* ---------------------------------- List-type here:
// variable lines_before_and_after_sciname is important. It is the lines before and after the "list header".
---------------------------------- */

$pdf_id = pathinfo($input['filename'], PATHINFO_FILENAME);
if(Functions::is_production()) $input['epub_output_txts_dir'] = '/extra/other_files/Smithsonian/MoftheAES/'.$pdf_id.'/';
else                           $input['epub_output_txts_dir'] = '/Volumes/AKiTiO4/other_files/Smithsonian/MoftheAES/'.$pdf_id.'/';
// /*
$folder = $input['epub_output_txts_dir'];
if(!is_dir($folder)) mkdir($folder);
$postfix = array("_tagged.txt", "_tagged_LT.txt", "_edited.txt", "_edited_LT.txt", "_descriptions_LT.txt");
foreach($postfix as $post) {
    $txt_filename = pathinfo($folder, PATHINFO_BASENAME)."$post";
    $txt_filename = $folder."/".$txt_filename;
    echo "\n$txt_filename - ";
    if(file_exists($txt_filename)) if(unlink($txt_filename)) echo " deleted OK\n";
    // else                                                     echo " does not exist OK\n";
}
// exit("\n-end for now-\n");
// */
// print_r($input); exit;
$func->parse_pdftotext_result($input);

/* a utility - copied template
$func->utility_download_txt_files();
*/
/*
wget https://editors.eol.org/other_files/Smithsonian/epub_10088_5097/SCtZ-0437/SCtZ-0437.txt
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>