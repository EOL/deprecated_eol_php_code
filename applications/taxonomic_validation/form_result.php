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

/* 1st try --- problematic
if($val = @$form['Filename_ID']) $time_var = $val;
else                             $time_var = time();
*/

$form_url = @get_val_var('form_url');

if($form_url) { //URL is pasted.
    $orig_file = pathinfo($form_url, PATHINFO_BASENAME);
    $newfile = $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
    
    if(!in_array(pathinfo($form_url, PATHINFO_EXTENSION), array('xls', 'xlsx', 'zip'))) exit("\nERROR: Wrong file format.\n\n");
    // print_r(pathinfo($form_url)); exit;
    
    /* good debug
    echo "<hr>form_url: [$form_url]";
    echo "<hr>orig_file: [$orig_file]";
    echo "<hr>newfile: [$newfile]<hr>";
    // exit;
    */
}
elseif($file_type = @$_FILES["file_upload"]["type"]) {
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
        if($_FILES["file_upload"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload"]["name"];
            $url = "temp/" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
        // echo "<hr>file_type: [$file_type]";
        // echo "<hr>orig_file: [$orig_file]";
        // echo "<hr>url: [$url]";
        // echo "<hr>newfile: [$newfile]<hr>";
        // exit;
    }
    else exit("<hr>$file_type<hr>Invalid file type. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
elseif($file_type = @$_FILES["file_upload3"]["type"]) {
    debug("<br>orig_file: [".$_FILES["file_upload"]["name"]."]<br>"); debug("<br>file type: [".$file_type."]<br>");
    $allowed_file_types = array("text/plain", "application/zip");
    if(in_array($file_type, $allowed_file_types)) {
        if($_FILES["file_upload3"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload3"]["name"];
            $url = "temp/" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload3"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);

        // /* ---------- Added block:
        require_library('connectors/TaxonomicValidationRules');
        require_library('connectors/TaxonomicValidationAPI');
        if(pathinfo($newfile, PATHINFO_EXTENSION) == "zip") { //e.g. taxa_list.txt.zip
            $func1 = new TaxonomicValidationAPI('taxonomic_validation');
            $newfile = $func1->process_zip_file(str_replace("temp/", "", $newfile)); // exit("\n[$newfile]\n");

            $newfile = "temp/".$newfile;
            $func2 = new TaxonomicValidationRules();
            $func2->add_header_to_file($newfile, "scientificName");
        }
        else { //e.g. taxa_list.txt
            $func2 = new TaxonomicValidationRules();
            $func2->add_header_to_file($newfile, "scientificName");
        }
        // ---------- */
    }
    else exit("<hr>$file_type<hr>Invalid file type. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");    
}
else exit("<hr>Please select a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");

/* replaced by Jenkins call
print "<br><b>Processing, please wait...</b><br><hr>";
print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile&orig_file=$orig_file'>";
exit;
*/

if(Functions::is_production()) {
    $for_DOC_ROOT = '/html/eol_php_code/';
    $true_DOC_ROOT = DOC_ROOT;
}
else {
    $for_DOC_ROOT = DOC_ROOT;
    $true_DOC_ROOT = $for_DOC_ROOT;
}

// if($form_url)   require_once("jenkins_call_4url.php");
// else            require_once("jenkins_call.php");

$form = $_POST;

require_once("jenkins_call.php");

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
?>