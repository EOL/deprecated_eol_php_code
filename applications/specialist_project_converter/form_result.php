<?php

$url = get_val_var('url');
$orig_url = $url;

$parts = pathinfo($url);
$extension = @$parts['extension'];

$newfile = "temp/" . time() . "." . $extension;

if($url != ""){}//URL is pasted.
elseif(isset($_FILES["file_upload"]["type"]))
{   
    if($_FILES["file_upload"]["type"] == "application/vnd.ms-excel" or
       $_FILES["file_upload"]["type"] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" 
      ) 
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
}

function get_val_var($v)
{
    if     (isset($_GET["$v"]))$var=$_GET["$v"];
    elseif (isset($_POST["$v"]))$var=$_POST["$v"];    
    if(isset($var)) return $var;
    else return NULL;
}
?>