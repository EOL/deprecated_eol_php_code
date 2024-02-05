<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
// */

/* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
*/
$time_var = time();

// echo "<pre>"; print_r($_FILES); exit("</pre>");
$form = $_POST;
// echo "<pre>"; print_r($form); echo "</pre>"; exit("\neli 200\n");
/*Array(
    [form_url] => 
    [Filename_ID] => 111
)*/

// /* Filename_ID check if doesn't exist in OpenData. If doesn't exist, stop operation now.
if($Filename_ID = @get_val_var('Filename_ID')) {
    require_library('connectors/TraitDataImportAPI');
    $func = new TraitDataImportAPI('trait_data_import');
    if($resource_id = $func->get_ckan_resource_id_given_hash("hash-".$Filename_ID)) {} //continue;
    else exit("<hr>Upload ID [$Filename_ID] does not exist. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
// */

/* not used here
$form_url = @get_val_var('form_url');
if($form_url) { //URL is pasted.
    $orig_file = pathinfo($form_url, PATHINFO_BASENAME);
    $newfile = $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);    
    if(!in_array(pathinfo($form_url, PATHINFO_EXTENSION), array('xls', 'xlsx', 'zip'))) exit("\nERROR: Wrong file format.\n\n");
    // print_r(pathinfo($form_url)); exit;
    // good debug
    // echo "<hr>form_url: [$form_url]";
    // echo "<hr>orig_file: [$orig_file]";
    // echo "<hr>newfile: [$newfile]<hr>";
    // exit;
}
*/

if($file_type = @$_FILES["file_upload"]["type"]) { // File A
    debug("<br>orig_file: [".$_FILES["file_upload"]["name"]."]<br>");
    debug("<br>file type: [".$file_type."]<br>"); 
    // echo "<pre>"; print_r($_FILES); echo "</pre>"; exit; //good debug
    /*
    [taxon.tab] [application/octet-stream]
    [taxon.tsv] [text/tab-separated-values]
    [taxon.txt] [text/plain]
    [taxon.csv] [text/csv]
    */
    $allowed_file_types = array("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-excel", "application/zip");
    $allowed_file_types = array("application/octet-stream", "text/tab-separated-values", "text/plain", "text/csv", "application/zip"); //

    if(in_array($file_type, $allowed_file_types)) {
        $upload_error = $_FILES["file_upload"]["error"];
        if($upload_error > 0) exit_now("<hr>$upload_error<hr>File A: File upload error.");
        else {
            $orig_file = $_FILES["file_upload"]["name"];
            $orig_file_A = $orig_file;
            $destination = "File_A_" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload"]["tmp_name"] , "temp/".$destination)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile_File_A = $destination;
        // echo "<hr>file_type: [$file_type]";
        // echo "<hr>orig_file: [$orig_file]";
        // echo "<hr>newfile: [$newfile]<hr>"; exit;
        // /* ---------- Added block:
        if(pathinfo($newfile_File_A, PATHINFO_EXTENSION) == "zip") { //e.g. taxon.tab.zip
            require_library('connectors/BranchGraftRules');
            require_library('connectors/BranchGraftAPI');
            $func = new BranchGraftAPI('branch_graft');
            $filename_2_unzip = pathinfo($newfile_File_A, PATHINFO_BASENAME); // File_A_1688382076.zip
            // $newfile_File_A = "temp/".$func->process_zip_file($filename_2_unzip);
            $newfile_File_A =            $func->process_zip_file($filename_2_unzip);
        }
        // ---------- */
        /* ---------- Added block: should work but not used here.
        if(strtolower(pathinfo($newfile, PATHINFO_EXTENSION)) == 'csv') { // echo "<br>csv nga<br>";
            require_library('connectors/TaxonomicValidationRules');
            require_library('connectors/TaxonomicValidationAPI');
            $func = new TaxonomicValidationAPI('taxonomic_validation');
            $newfile = $func->convert_csv2tsv($newfile); // exit("\n[$newfile]\n");
        }
        // else echo "<br>hindi csv<br>";
        // print_r(pathinfo($newfile)); exit("<br>$newfile<br>stop 1<br>");
        ---------- */
    }
    else exit_now("<hr>$file_type<hr>File A: Invalid file type.");
}
if($file_type = @$_FILES["file_upload2"]["type"]) { // File B
    debug("<br>orig_file: [".$_FILES["file_upload2"]["name"]."]<br>"); debug("<br>file type: [".$file_type."]<br>");
    $allowed_file_types = array("application/x-gzip", "application/zip"); //.tar.gz and .zip
    $allowed_file_types = array("application/octet-stream", "text/tab-separated-values", "text/plain", "text/csv", "application/zip"); //
    if(in_array($file_type, $allowed_file_types)) {
        $upload_error = $_FILES["file_upload2"]["error"];
        if($upload_error > 0) exit_now("<hr>$upload_error<hr>File B: File upload error.");
        else {
            $orig_file = $_FILES["file_upload2"]["name"];
            $orig_file_B = $orig_file;
            $destination = "File_B_" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload2"]["tmp_name"] , "temp/".$destination)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile_File_B = $destination;
        // echo "<hr>file_type: [$file_type]";
        // echo "<hr>orig_file: [$orig_file]";
        // echo "<hr>newfile: [$newfile]<hr>"; exit;
        // /* ---------- Added block:
        if(pathinfo($newfile_File_B, PATHINFO_EXTENSION) == "zip") { //e.g. taxon.tab.zip
            require_library('connectors/BranchGraftRules');
            require_library('connectors/BranchGraftAPI');
            $func = new BranchGraftAPI('branch_graft');
            $filename_2_unzip = pathinfo($newfile_File_B, PATHINFO_BASENAME); // File_A_1688382076.zip
            // $newfile_File_B = "temp/".$func->process_zip_file($filename_2_unzip);
            $newfile_File_B =            $func->process_zip_file($filename_2_unzip);
        }
        // ---------- */
        /* ---------- Added block: should work but not used here.
        if(strtolower(pathinfo($newfile, PATHINFO_EXTENSION)) == 'csv') { // echo "<br>csv nga<br>";
            require_library('connectors/TaxonomicValidationRules');
            require_library('connectors/TaxonomicValidationAPI');
            $func = new TaxonomicValidationAPI('taxonomic_validation');
            $newfile = $func->convert_csv2tsv($newfile); // exit("\n[$newfile]\n");
        }
        ---------- */
    }
    else exit_now("<hr>$file_type<hr>File B: Invalid file type.");
}

$fileA_taxonID = @get_val_var('fileA_taxonID');
$fileB_taxonID = @get_val_var('fileB_taxonID');
if(@$_FILES["file_upload"]["type"] && @$_FILES["file_upload2"]["type"] && $fileA_taxonID) {}
else {
    // print_r(@$_FILES["file_upload"]); print_r(@$_FILES["file_upload2"]); //nothing to display
    exit("<hr>Please select a file to continue. <br><br>Or in the case of very big files, try to zip it.<br><br><a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}

/* replaced by Jenkins call
print "<br><b>Processing, please wait...</b><br><hr>";
print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile&orig_file=$orig_file'>";
exit;
*/

if(Functions::is_production()) {
    $for_DOC_ROOT =  '/var/www/html/eol_php_code/'; //'/html/eol_php_code/';
    $true_DOC_ROOT = DOC_ROOT;
}
else {
    $for_DOC_ROOT = DOC_ROOT;
    $true_DOC_ROOT = $for_DOC_ROOT;
}

// if($form_url)   require_once("jenkins_call_4url.php");
// else            require_once("jenkins_call.php");

$form = $_POST; echo "<pre>";print_r($form);echo "</pre>";
require_once("jenkins_call.php"); // normal operation

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
function exit_now($msg)
{   
    exit($msg . " <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
?>