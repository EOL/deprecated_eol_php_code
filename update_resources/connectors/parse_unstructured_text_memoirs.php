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
118935	Wed 2021-06-23 02:59:52 AM	    {                "media_resource.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.1, "min":0.02, "hr":0}}
118935_ENV	Wed 2021-06-23 03:01:06 AM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":13.62, "min":0.23, "hr":0}}
removed 'Chin' in eol-geonames
118935	Thu 2021-06-24 12:33:22 AM	    {                "media_resource.tab":1309,                   "taxon.tab":1308, "time_elapsed":{"sec":1.09, "min":0.02, "hr":0}}
118935_ENV	Thu 2021-06-24 12:35:46 AM	{"MoF.tab":1447,                            "occur.tab":1447, "taxon.tab":1308, "time_elapsed":{"sec":83.93, "min":1.4, "hr":0.02}}
------------------------------------------------------------
120081	Tue 2021-06-22 12:43:28 PM	    {               "media_resource.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.92, "min":0.02, "hr":0}}
120081_ENV	Tue 2021-06-22 12:48:10 PM	{"MoF.tab":519, "media_resource.tab":95, "occur.tab":519, "taxon.tab":95, "time_elapsed":{"sec":280.95, "min":4.68, "hr":0.08}}
120081	Wed 2021-06-23 02:59:39 AM	    {               "media_resource.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
120081_ENV	Wed 2021-06-23 03:00:53 AM	{"MoF.tab":633, "media_resource.tab":95, "occur.tab":633, "taxon.tab":95, "time_elapsed":{"sec":14.16, "min":0.24, "hr":0}}
120081	Wed 2021-06-23 11:35:43 AM	    {               "media_resource.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":3.21, "min":0.05, "hr":0}}
120081_ENV	Wed 2021-06-23 11:37:11 AM	{"MoF.tab":633, "media_resource.tab":95, "occur.tab":633, "taxon.tab":95, "time_elapsed":{"sec":24.65, "min":0.41, "hr":0.01}}
removed 'Chin' in eol-geonames
120081	Thu 2021-06-24 12:31:23 AM	    {               "media_resource.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
120081_ENV	Thu 2021-06-24 12:32:56 AM	{"MoF.tab":632, "media_resource.tab":95, "occur.tab":632, "taxon.tab":95, "time_elapsed":{"sec":33.1, "min":0.55, "hr":0.01}}
remove traits in eol-geonames if inside literature reference
120081	Fri 2021-06-25 08:13:25 AM	    {               "media_resource.tab":95,                  "taxon.tab":95, "time_elapsed":{"sec":2.23, "min":0.04, "hr":0}}
120081_ENV	Fri 2021-06-25 08:15:48 AM	{"MoF.tab":523, "media_resource.tab":95, "occur.tab":523, "taxon.tab":95, "time_elapsed":{"sec":81.53, "min":1.36, "hr":0.02}}
------------------------------------------------------------
120082	Thu 2021-06-24 11:27:32 AM	    {                                       "media_resource.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":0.37, "min":0.01, "hr":0}}
120082_ENV	Thu 2021-06-24 11:31:27 AM	{"measurement_or_fact_specific.tab":92, "media_resource.tab":25, "occurrence_specific.tab":92, "taxon.tab":25, "time_elapsed":{"sec":175.03, "min":2.92, "hr":0.05}}
remove Distrito Federal,https://www.geonames.org/3463504
120082	Fri 2021-06-25 07:37:02 AM	    {                                       "media_resource.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":3.79, "min":0.06, "hr":0}}
120082_ENV	Fri 2021-06-25 07:38:51 AM	{"measurement_or_fact_specific.tab":91, "media_resource.tab":25, "occurrence_specific.tab":91, "taxon.tab":25, "time_elapsed":{"sec":48.61, "min":0.81, "hr":0.01}}
remove traits in eol-geonames if inside literature reference
120082	Fri 2021-06-25 07:59:19 AM	    {                                       "media_resource.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":1.44, "min":0.02, "hr":0}}
120082_ENV	Fri 2021-06-25 08:00:49 AM	{"measurement_or_fact_specific.tab":61, "media_resource.tab":25, "occurrence_specific.tab":61, "taxon.tab":25, "time_elapsed":{"sec":28.45, "min":0.47, "hr":0.01}}
120082	Mon 2021-06-28 07:59:54 AM	    {                                       "media_resource.tab":25,                               "taxon.tab":25, "time_elapsed":{"sec":0.52, "min":0.01, "hr":0}}
120082_ENV	Mon 2021-06-28 08:01:23 AM	{"measurement_or_fact_specific.tab":61, "media_resource.tab":25, "occurrence_specific.tab":61, "taxon.tab":25, "time_elapsed":{"sec":27.93, "min":0.47, "hr":0.01}}
------------------------------------------------------------
118986	Tue 2021-06-29 11:36:55 PM	    {                                        "media_resource.tab":41,                                "taxon.tab":41, "time_elapsed":{"sec":0.4, "min":0.01, "hr":0}}
118986_ENV	Tue 2021-06-29 11:48:52 PM	{"measurement_or_fact_specific.tab":512, "media_resource.tab":41, "occurrence_specific.tab":512, "taxon.tab":41, "time_elapsed":{"sec":657.64, "min":10.96, "hr":0.18}}
118986	Wed 2021-06-30 01:32:52 AM	    {                                        "media_resource.tab":41,                                "taxon.tab":41, "time_elapsed":{"sec":1.88, "min":0.03, "hr":0}}
118986_ENV	Wed 2021-06-30 01:36:21 AM	{"measurement_or_fact_specific.tab":512, "media_resource.tab":41, "occurrence_specific.tab":512, "taxon.tab":41, "time_elapsed":{"sec":148.16, "min":2.47, "hr":0.04}}
118986	Thu 2021-07-01 01:25:48 AM	    {                                        "media_resource.tab":41,                                "taxon.tab":41, "time_elapsed":{"sec":0.58, "min":0.01, "hr":0}}
118986_ENV	Thu 2021-07-01 01:27:42 AM	{"measurement_or_fact_specific.tab":512, "media_resource.tab":41, "occurrence_specific.tab":512, "taxon.tab":41, "time_elapsed":{"sec":54.05, "min":0.9, "hr":0.02}}
------------------------------------------------------------
118920	Wed 2021-06-30 07:48:29 AM	    {                                       "media_resource.tab":27,                               "taxon.tab":27, "time_elapsed":{"sec":0.36, "min":0.01, "hr":0}}
118920_ENV	Wed 2021-06-30 07:52:50 AM	{"measurement_or_fact_specific.tab":74, "media_resource.tab":27, "occurrence_specific.tab":74, "taxon.tab":27, "time_elapsed":{"sec":200.19, "min":3.34, "hr":0.06}}
------------------------------------------------------------

php5.6 parse_unstructured_text_memoirs.php jenkins '{"resource_id": "118935", "resource_name":"1st doc"}'
php5.6 parse_unstructured_text_memoirs.php jenkins '{"resource_id": "120081", "resource_name":"2nd doc"}'

parse_unstructured_text_memoirs.php _ '{"resource_id": "118935", "resource_name":"1st doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120081", "resource_name":"2nd doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120082", "resource_name":"4th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118986", "resource_name":"5th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "118920", "resource_name":"6th doc"}'
parse_unstructured_text_memoirs.php _ '{"resource_id": "120083", "resource_name":"7th doc"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS["ENV_DEBUG"] = true;
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
/*--------------------------------------------------------------------------------------------------------------*/
$rec[118935] = array('filename' => '118935.txt', 'lines_before_and_after_sciname' => 1); /*1 stable stats: blocks: 1322  Raw scinames count: 1322 */
$rec[120081] = array('filename' => '120081.txt', 'lines_before_and_after_sciname' => 2); /*2 stable stats: blocks: 97    Raw scinames count: 98 */
$rec[120082] = array('filename' => '120082.txt', 'lines_before_and_after_sciname' => 2); /*4 stable stats: blocks: 25    Raw scinames count: 25 */
$rec[118986] = array('filename' => '118986.txt', 'lines_before_and_after_sciname' => 2); /*5 stable stats: blocks: 43    Raw scinames count: 43 */
$rec[118920] = array('filename' => '118920.txt', 'lines_before_and_after_sciname' => 2); /*6 stable stats: blocks: 27    Raw scinames count: 27 */
$rec[120083] = array('filename' => '120083.txt', 'lines_before_and_after_sciname' => 2); /*7 stable stats: blocks: 193   Raw scinames count: 191 
                                                                                           wc -l -> 193 120083_descriptions_LT.txt */
                                                                                           
/* TO DO: 
doc 5: didn't get a valid binomial: "Laccophilus spergatus Sharp (Figs. 98-105, 297)"
*/

/*--------------------------------------------------------------------------------------------------------------*/
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