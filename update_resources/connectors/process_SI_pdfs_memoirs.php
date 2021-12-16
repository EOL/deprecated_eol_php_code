<?php
namespace php_active_record;
/*
https://repository.si.edu/handle/10088/5097         1st repo
https://repository.si.edu/handle/10088/6943         2nd repo
--------------------------------------------------
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/10088_5097_ENV.tar.gz
--------------------------------------------------
php5.6 process_SI_pdfs_memoirs.php jenkins '{"resource_id": "118935", "resource_name":"1st doc"}'
php5.6 process_SI_pdfs_memoirs.php jenkins '{"resource_id": "120081", "resource_name":"2nd doc"}'
php5.6 process_SI_pdfs_memoirs.php jenkins '{"resource_id": "MoftheAES", "resource_name":"all resources"}'

process_SI_pdfs_memoirs.php _ '{"resource_id": "118935", "resource_name":"1st doc", "IOReport": "NAF_first7"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "120081", "resource_name":"2nd doc"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "120082", "resource_name":"4th doc"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118986", "resource_name":"5th doc"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118920", "resource_name":"6th doc"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "120083", "resource_name":"7th doc"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118237", "resource_name":"8th doc"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "MoftheAES", "resource_name":"all resources"}' --- USED, another option is: aggregate_MoftheAES.php.
process_SI_pdfs_memoirs.php _ '{"resource_id": "30355", "resource_name":"others", "IOReport":"MotAES"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "27822", "resource_name":"others"}'

process_SI_pdfs_memoirs.php _ '{"resource_id": "30353", "resource_name":"others"}' // to be skipped
process_SI_pdfs_memoirs.php _ '{"resource_id": "30354", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "119035", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118946", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118936", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118950", "resource_name":"others"}'
Jul 20 Mon
process_SI_pdfs_memoirs.php _ '{"resource_id": "120602", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "119187", "resource_name":"others"}'
Jul 27 Tue
process_SI_pdfs_memoirs.php _ '{"resource_id": "118978", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "118941", "resource_name":"others"}'
Jul 28 Wed
process_SI_pdfs_memoirs.php _ '{"resource_id": "119520", "resource_name":"others"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "119188", "resource_name":"others"}'


=== START BHL RESOURCES ===
process_SI_pdfs_memoirs.php _ '{"resource_id": "15423", "resource_name":"1st BHL", "doc": "BHL"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "15423", "resource_name":"NAF", "doc": "BHL"}'       --- changed to "NAF" for DATA-1891
process_SI_pdfs_memoirs.php _ '{"resource_id": "91155", "resource_name":"NAF", "doc": "BHL"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "15427", "resource_name":"NAF", "doc": "BHL"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "15428", "resource_name":"4th BHL", "doc": "BHL"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "91144", "resource_name":"5th BHL", "doc": "BHL"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "91225", "resource_name":"6th BHL", "doc": ""}' //host-pathogen list pattern
process_SI_pdfs_memoirs.php _ '{"resource_id": "91362", "resource_name":"7th BHL", "doc": ""}' //host-pathogen list pattern
process_SI_pdfs_memoirs.php _ '{"resource_id": "91362_species", "resource_name":"7th BHL", "doc": "BHL"}' //species sections for 91362

BHL Fungi:
process_SI_pdfs_memoirs.php _ '{"resource_id": "15404",          "resource_name":"NAF", "doc": "BHL", "IOReport":"NAF_Fungi"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "'$resource_ID'", "resource_name":"nth BHL", "doc": "BHL"}'
BHL Plants:
process_SI_pdfs_memoirs.php _ '{"resource_id": "15422",          "resource_name":"nth BHL", "doc": "BHL", "IOReport":"NAF_Plants"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "91209",          "resource_name":"nth BHL", "doc": "BHL", "IOReport":"NAF_Plants"}'

Kubitzki
process_SI_pdfs_memoirs.php _ '{"resource_id": "volii1993",     "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "voliii1998",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volv2003",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
Sep 20 Monday
process_SI_pdfs_memoirs.php _ '{"resource_id": "volvi2004",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volvii2004",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volviii2007",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
Sep 21 Tuesday
process_SI_pdfs_memoirs.php _ '{"resource_id": "volix2007",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volx2011",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volxi2014",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
Sep 22 Wed
process_SI_pdfs_memoirs.php _ '{"resource_id": "volxii2015",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volxiii2015",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volxiv2016",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'
process_SI_pdfs_memoirs.php _ '{"resource_id": "volxv2018",    "resource_name":"Kubitzki", "doc": "Kubitzki_et_al"}'

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
// $GLOBALS["ENV_DEBUG"] = false;
$timestart = time_elapsed();
// print_r($argv); exit;
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$resource_name = @$param['resource_name']; //right now used in Media -> derivedFrom column
$doc = @$param['doc'];

/*
// .05 to .16
$row = "with numerous setae .05 to .16 mm."; echo "\n$row\n";
$row = format_number_number_range($row); exit("\n$row\n");
$str = ".05-.16";
if(is_numeric($str)) echo "\n[$str] is numeric\n";
else                 echo "\n[$str] is not numeric\n";
exit("\n-end test-\n");
*/


/* un-comment in real operation - main operation --- WASN'T USED HERE IN Memoirs
require_library('connectors/ParseListTypeAPI');
require_library('connectors/SmithsonianPDFsAPI');
$func = new SmithsonianPDFsAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
*/

/* ========================== during dev: processing associations ========================== --- WASN'T USED HERE IN Memoirs
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
if($resource_id == 'MoftheAES') {
    require_library('connectors/Functions_Memoirs');
    require_library('connectors/ParseListTypeAPI_Memoirs');
    require_library('connectors/SmithsonianPDFsAPI_Memoirs');
    $func = new SmithsonianPDFsAPI_Memoirs($resource_id);
    $func->initialize();
    $func->generate_dwca_for_a_repository(); //for all files in entire repo: "Memoirs of the American Entomological Society"
    $func->archive_builder_finalize();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
}
else { //run individual documents
    // $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0605/SCtZ-0605_descriptions_LT.txt";  $pdf_id = "SCtZ-0605";
    // $txt_filename = "/Volumes/AKiTiO4/other_files/Smithsonian/epub_10088_5097/SCtZ-0614/SCtZ-0614_descriptions_LT.txt";  $pdf_id = "SCtZ-0614";

    if(!$doc) $doc = 'MoftheAES';
    if(Functions::is_production()) $path = '/extra/other_files/Smithsonian/'.$doc.'/'.$resource_id.'/';
    else                           $path = '/Volumes/AKiTiO4/other_files/Smithsonian/'.$doc.'/'.$resource_id.'/';
    $txt_filename = $path . $resource_id."_descriptions_LT.txt";
    $pdf_id = $resource_id;
    require_library('connectors/Functions_Memoirs');
    require_library('connectors/ParseListTypeAPI_Memoirs');
    require_library('connectors/SmithsonianPDFsAPI_Memoirs');
    $func = new SmithsonianPDFsAPI_Memoirs($resource_id);
    $func->initialize($resource_name, $param); //$resource_name --- right now used in Media -> derivedFrom column
    // for single file, during dev for list-type =====
    if(file_exists($txt_filename)) $func->process_a_txt_file_LT($txt_filename, $pdf_id, array());
    $txt_filename = str_replace("_descriptions_LT", "_tagged", $txt_filename);
    if(file_exists($txt_filename)) $func->process_a_txt_file($txt_filename, $pdf_id, array());
    // =====
    $func->archive_builder_finalize();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
}
// ========================== end LIST-TYPE ==========================*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>