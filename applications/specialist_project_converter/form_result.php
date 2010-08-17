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

if($orig_url)$arr = parse_url($orig_url);
else $arr = parse_url($url);


$validate = get_val_var('validate');
//print $validate; //exit;


if($validate == 'on')
{    
    $path_parts = pathinfo(__FILE__);
    $temp = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];  
    $temp = str_ireplace($path_parts["basename"], "", $temp);
    //print"<i><font size='2'>$temp</font></i>"; exit;

    //$fn = "http://mydomain.org/eol_php_code/applications/xls2eol/" . $newfile . "";
    $fn = $temp . $newfile . "";    
    $fn = $temp . "form_result.php?url=" . urlencode($fn) . "";    
    
    //<form name='validator_form' action='http://services.eol.org/validator/index.php' method='post'>
    print"
    <i>Transformation done.</i> <p>
    <form name='validator_form' action='http://services.eol.org/eol_php_code/applications/validator/index.php' method='post'>
    <input type='hidden' size='30' name='file_url' value='$fn'>
    <input type='submit' value='Click here to Validate >> '>
    </td>    
    </form>
    <p><a href='javascript:history.go(-1)'> &lt;&lt; Back to menu</a>
    ";
    
    //Please wait. Forwarded to validation...    ";     
    exit;    
    /*
    <META HTTP-EQUIV='Refresh' Content='$secs; URL=$url_str'>    
    exit; 
    */
    
    ?>
    <script language="javascript1.2">document.forms.validator_form.submit()</script>
    <?php
    exit;
}


print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile'>";
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