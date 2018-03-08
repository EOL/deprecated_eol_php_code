<?php
namespace php_active_record;

/* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
*/

// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
// */

$GLOBALS['ENV_DEBUG'] = true;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

echo "<hr>";
print_r(@$GLOBALS);
echo "<hr>";


$url = @get_val_var('url');

// echo "<pre>";
// print_r(@$_FILES);
// echo "</pre>";
// exit;

if($url) { //URL is pasted.
    $parts = pathinfo($url);
    $extension = @$parts['extension'];
    $newfile = "temp/" . time() . "." . $extension;
    $orig_file = $parts['basename'];
}
elseif($file_type = @$_FILES["file_upload"]["type"]) {
    if(in_array($file_type, array("application/octet-stream", "text/plain", "text/tab-separated-values", "application/zip"))) { //for spreadsheets: "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        if($_FILES["file_upload"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload"]["name"];
            // $url = "temp/" . $orig_file;
            $url = "temp/" . time() . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $url)) {
                echo "<br>file uploaded - OK<br>";
                echo "<br>destination: $url<br>";
                // exit("<br>testing...exits now...<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . time() . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
    }
    else exit("<hr>$file_type<hr>Invalid file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
// else exit("<hr>Please enter a URL or browse a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
else exit("<hr>Please browse a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");


// if(!copy($url, $newfile)) exit("<hr>Failed to copy file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");

$validate = get_val_var('validate');
print "<br><b>Processing, please wait...</b><br><hr>";
print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile&orig_file=$orig_file'>";
exit;

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}

/*
function excel_extension($type) { //not used
    if      ($type == "application/vnd.ms-excel") return "xls";
    elseif  ($type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") return "xlsx";
    elseif  ($type == "application/octet-stream") return "eli";
}
*/

/*
elseif(@$_FILES["file_upload_new"]["type"]) {
    if(in_array($_FILES["file_upload_new"]["type"],
        array("application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/octet-stream"))) {
        if ($_FILES["file_upload_new"]["error"] > 0) {}
        else {
            require_library('ExcelToText');
            $new_temp_path = DOC_ROOT . "temp/" . $_FILES["file_upload_new"]["name"];
            move_uploaded_file($_FILES["file_upload_new"]["tmp_name"] , $new_temp_path);
            $dwca_url = ExcelToText::worksheet_to_file($new_temp_path);
        }
    }
    else exit("<hr>Invalid file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a>");
    
    echo "Download your archive at <a href='$dwca_url'>$dwca_url</a>\n";
    
    if(preg_match("/\/(tmp\/dwca_[0-9]+)\./", $dwca_url, $arr)) {
        echo $arr[1];
        $archive = new ContentArchiveReader(null, DOC_ROOT . $arr[1]);
        $validator = new ContentArchiveValidator($archive);
        $validator->get_validation_errors();
        $errors = array_merge($validator->structural_errors(), $this->validator->display_errors());
        if($errors) {
            echo "<h2>Errors</h2>";
            foreach($e as $error) {
                echo "Error in $error->file on line $error->line field $error->uri: $error->message [\"$error->value\"]<br/><br/>";
            }
        }
        if($w = $validator->display_warnings()) {
            echo "<h2>Warnings</h2>";
            foreach($w as $warning) {
                echo "Warning in $warning->file on line $warning->line field $warning->uri: $warning->message [\"$warning->value\"]<br/><br/>";
            }
        }
    }
    exit;
}
*/

?>
