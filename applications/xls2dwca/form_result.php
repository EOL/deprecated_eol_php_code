<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$url = $_REQUEST['url'];
$upload = $_FILES["file_upload"];


$suffix = null;
if(@$upload['tmp_name'])
{
    $url = $upload['tmp_name'];
    if(preg_match("/\.([^\.]+)$/", $upload['name'], $arr)) $suffix = strtolower(trim($arr[1]));
}

/*
echo "<hr><pre>";
print_r($_REQUEST);
print_r($_FILES);
echo "</pre>";
echo "<br>url = [$url]<br>";
echo "<br>suffix = [$suffix]<br>";
*/

$final_archive_gzip_url = null;
$errors = array();
if($url)
{
    if($temp_dir = ContentManager::download_temp_file_and_assign_extension($url, $suffix, array('suffix' => $suffix, 'timeout' => 900))) //15 minutes timeout (900 seconds)
    {
        if(is_dir($temp_dir))
        {
            $errors[] = "The file provided ". $temp_dir ." is not an Excel file";
            recursive_rmdir($temp_dir);
        }else
        {
            $path_parts = pathinfo($temp_dir);
            $extension = @$path_parts['extension'];
            $archive_tmp_dir = @$path_parts['dirname'] ."/". @$path_parts['filename'];
            if($extension == 'xlsx' || $extension == 'xls')
            {
                require_library('ExcelToText');
                $archive_converter = new ExcelToText($temp_dir);
                if($archive_converter->errors())
                {
                    $errors = $archive_converter->errors();
                }elseif($archive_converter->is_new_schema_spreadsheet())
                {
                    $archive_tmp_dir = $archive_converter->convert_to_new_schema_archive();
                    if($archive_converter->errors())
                    {
                        $errors = $archive_converter->errors();
                        recursive_rmdir($archive_tmp_dir);
                    }else
                    {
                        if(preg_match("/\/(dwca_[0-9]+)$/", $archive_tmp_dir, $arr))
                        {
                            $final_archive_gzip_file = DOC_ROOT . "tmp/" . $arr[1] . ".tar.gz";
                            if($arr[1]) {
                                $temp_folder = DOC_ROOT . "tmp/" . $arr[1];
                                recursive_rmdir($temp_folder); // remove dir e.g. dwca_xxxxx
                                echo "<hr>[temp_folder deleted: $temp_folder]<hr>";
                            }
                            
                            //start copy to /resources/xls2dwca/ folder
                            $resource_xls2dwca_folder = CONTENT_RESOURCE_LOCAL_PATH . "/xls2dwca";
                            if(!is_dir($resource_xls2dwca_folder)) mkdir($resource_xls2dwca_folder);
                            copy($final_archive_gzip_file, $resource_xls2dwca_folder . "/". $arr[1] . ".tar.gz");
                            unlink($final_archive_gzip_file);
                            $final_archive_gzip_url = WEB_ROOT . "applications/content_server/resources/xls2dwca/" . $arr[1] . ".tar.gz";
                        }
                    }
                }else $errors[] = "Unable to determine the template of the provided Excel file. Are you sure this matches the EOL template provided at https://github.com/eliagbayani/EOL-connector-data-files/raw/master/schema/eol_import_spreadsheet.xlsx ?";
            }else $errors[] = "The file provided is not an Excel file";
            unlink($temp_dir);
        }
    }else
    {
        $errors[] = "There was a problem with the uploaded $suffix file.";
    }
}else $errors[] = "No file was provided";


if($final_archive_gzip_url)
{
    echo "<br>=======================================================<br>";
    echo "The archive is now available at <a href='$final_archive_gzip_url'>$final_archive_gzip_url</a><br><br>
        You can use this as the resource URL in the EOL Content Partner Registry (in the Resources section),
        or you can save this file to your computer.<br><br>
        This file will be stored on our server for two (2) weeks, after which it will be removed.<br><br>
        You can also use this URL in our
        <a href='../dwc_validator/index.php' target='_blank'>Archive Validator</a> to ensure its contents are valid.
        <a href='../dwc_validator/index.php?file_url=$final_archive_gzip_url' target='_blank'>Click here</a> to validate now.<br><br>
        Thank you.<br><br><a href='index.php'>Try another</a>";
    echo "<br>=======================================================<br><br>";
}else
{
    echo "There were problems processing this file:<br><br>";
    if($errors)
    {
        foreach($errors as $error)
        {
            echo "$error<br>";
        }
    }else echo "An unknown error occurred<br>";
}

?>
