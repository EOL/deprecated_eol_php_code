<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");

$url = @get_val_var('url');
$url_new = @get_val_var('url_new');
$orig_url = $url;

$parts = pathinfo($url);
$extension = @$parts['extension'];

$newfile = "temp/" . time() . "." . $extension;

if($url != ""){}//URL is pasted.
elseif($url_new != ""){}//URL is pasted.
elseif(@$_FILES["file_upload_new"]["type"])
{
    if(in_array($_FILES["file_upload_new"]["type"],
        array("application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/octet-stream")))
    {
        if ($_FILES["file_upload_new"]["error"] > 0){}
        else
        {
            require_library('ExcelToText');
            $new_temp_path = DOC_ROOT . "temp/" . $_FILES["file_upload_new"]["name"];
            move_uploaded_file($_FILES["file_upload_new"]["tmp_name"] , $new_temp_path);
            $dwca_url = ExcelToText::worksheet_to_file($new_temp_path);
        }
    }
    else exit("<hr>Invalid file. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");
    
    echo "Download your archive at <a href='$dwca_url'>$dwca_url</a>\n";
    
    if(preg_match("/\/(tmp\/dwca_[0-9]+)\./", $dwca_url, $arr))
    {
        echo $arr[1];
        $archive = new ContentArchiveReader(null, DOC_ROOT . $arr[1]);
        $validator = new ContentArchiveValidator($archive);
        $validator->get_validation_errors();
        $errors = array_merge($validator->structural_errors(), $this->validator->display_errors());
        if($errors)
        {
            echo "<h2>Errors</h2>";
            foreach($e as $error)
            {
                echo "Error in $error->file on line $error->line field $error->uri: $error->message [\"$error->value\"]<br/><br/>";
            }
        }
        if($w = $validator->display_warnings())
        {
            echo "<h2>Warnings</h2>";
            foreach($w as $warning)
            {
                echo "Warning in $warning->file on line $warning->line field $warning->uri: $warning->message [\"$warning->value\"]<br/><br/>";
            }
        }
    }
    
    exit;
}elseif(@$_FILES["file_upload"]["type"])
{
    if(in_array($_FILES["file_upload"]["type"],
        array("application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/octet-stream")))
    {
        if ($_FILES["file_upload"]["error"] > 0){}
        else
        {
            $url = "temp/" . $_FILES["file_upload"]["name"];
            move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $url);
        }        
        $newfile .= excel_extension($_FILES["file_upload"]["type"]);        
    }
    else exit("<hr>Invalid filex. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");
}
else exit("<hr>Please enter a URL or browse a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");
if (!copy($url, $newfile))exit("<hr>Failed to copy file. <br> <a href='javascript:history.go(-1)'> &lt;&lt;Go back</a>");

$validate = get_val_var('validate');
print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile&validate=$validate'>";
exit;

function excel_extension($type)
{
    if      ($type == "application/vnd.ms-excel")return "xls";
    elseif  ($type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")return "xlsx";
    elseif  ($type == "application/octet-stream") return "xls";
}
function get_val_var($v)
{
    if     (isset($_GET["$v"]))$var=$_GET["$v"];
    elseif (isset($_POST["$v"]))$var=$_POST["$v"];    
    if(isset($var)) return $var;
    else return NULL;
}
?>