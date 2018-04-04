<?php
namespace php_active_record;

require_once(dirname(__FILE__) ."/../../config/environment.php");

$mysqli = $GLOBALS['db_connection'];
$GLOBALS['ENV_DEBUG'] = false;

if(@$_FILES['dwca_upload']) $_POST['dwca_upload'] = $_FILES['dwca_upload'];
$parameters =& $_GET;
if(!$parameters) $parameters =& $_POST;

/* from original copied script (index.php)
require_once("controllers/validator.php");
$validator_controller = new dwc_validator_controller();
*/

// echo "<pre>*************"; print_r($parameters); echo "*************</pre>"; //good debug

/* Array(
    [file_url] => 
    [dwca_upload] => Array(
            [name] => 723_ggbn.tar.gz
            [type] => application/x-gzip
            [tmp_name] => /private/var/tmp/phpWGSHjx
            [error] => 0
            [size] => 410017
        )
)
*/


if(false) { //URL is pasted.
}
elseif($file_type = @$_FILES["dwca_upload"]["type"]) {
    if(in_array($file_type, array("application/x-gzip", "application/zip", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-excel"))) {
        $timex = time();
        if($_FILES["dwca_upload"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["dwca_upload"]["name"];
            // $url = "temp/" . $orig_file;
            $url = "temp/" . time() . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["dwca_upload"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . time() . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
    }
    else exit("<hr>$file_type<hr>Invalid file. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
else exit("<hr>File missing or maybe too big for the system. Please browse a new file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");

$newfile = DOC_ROOT . "applications/dwc_validator_jenkins/" . $newfile;
// echo "<br>newfile: [$newfile]<br>"; exit;

$parameters['dwca_upload']['tmp_name'] = $newfile;
$parameters['from_jenkins'] = '';

require_once("jenkins_call.php");

?>