<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

// /* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
// */

// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

// echo "<pre>"; print_r($_FILES); exit("</pre>");

$url = @get_val_var('url');

if($url) { //URL is pasted.
    $parts = pathinfo($url);
    $extension = @$parts['extension'];
    $newfile = "temp/" . time() . "." . $extension;
    $orig_file = $parts['basename'];
}
elseif($file_type = @$_FILES["file_upload"]["type"]) {
    if(in_array($file_type, array("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-excel", "application/zip"))) {
        if($_FILES["file_upload"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload"]["name"];
            $url = "temp/" . time() . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . time() . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
        
        // echo "<hr>orig_file: [$orig_file]";
        // echo "<hr>url: [$url]";
        // echo "<hr>newfile: [$newfile]<hr>";
        // exit;
    }
    else exit("<hr>$file_type<hr>Invalid file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
else exit("<hr>File maybe too big for the system. Please browse a new file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");

/* replaced by Jenkins call
print "<br><b>Processing, please wait...</b><br><hr>";
print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile&orig_file=$orig_file'>";
exit;
*/

if(Functions::is_production()) $for_DOC_ROOT = '/html/eol_php_code/';
else                           $for_DOC_ROOT = DOC_ROOT;

require_once("jenkins_call.php");

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
?>