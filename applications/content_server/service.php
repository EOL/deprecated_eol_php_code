<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('CyberSource');

$function = @$_GET["function"];
$server_ip = @$_GET["server_ip"];
$file_path = @$_GET["file_path"];
$agent_id = @$_GET["agent_id"];
$resource_id = @$_GET["resource_id"];
$amount = @$_GET["amount"];
$currecny = @$_GET["currecny"];
$type = @$_GET["type"];

if(!$function) $function = @$_POST["function"];
if(!$server_ip) $server_ip = @$_POST["server_ip"];
if(!$file_path) $file_path = @$_POST["file_path"];
if(!$agent_id) $agent_id = @$_POST["agent_id"];
if(!$resource_id) $resource_id = @$_POST["resource_id"];
if(!$amount) $amount = @$_POST["amount"];
if(!$currecny) $currecny = @$_POST["currecny"];
if(!$type) $type = @$_POST["type"];

/* Cybersource API is special */
if($function=="InsertSignature3")
{
    header('Content-type: text/plain');
    CyberSource::InsertSignature3($amount,$currecny,$type);
    exit;
}



header('Content-type: text/xml');
echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
echo "<response>\n";

switch($function)
{
    case "partner_image":
        $manager = new ContentManager($server_ip);
        $new_file_path = $manager->grab_file($file_path,0,"partner");
        if($new_file_path)
        {
            if(preg_match("/^([0-9]+)\./", $new_file_path, $arr)) $new_file_path = $arr[1];
            echo "  <file_prefix>$new_file_path</file_prefix>\n";
        }else
        {
            echo "  <error type='fatal'>Upload failed</error>\n";
        }
        break;
        
    case "upload_resource":
        if(!$resource_id)
        {
            echo "  <error type='fatal'>No resource_id included</error>\n";
            break;
        }
        if(!$file_path)
        {
            echo "  <error type='fatal'>No file_path included</error>\n";
            break;
        }
        
        // remove:
        //      resources/ID/*
        //      resources/ID
        //      resources/ID.*
        wildcard_rm(CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
        
        $manager = new ContentManager($server_ip);
        $new_file_path = $manager->grab_file($file_path, $resource_id, "resource");
        if($new_file_path)
        {
            if(is_dir($new_file_path))
            {
                validate_archive($new_file_path);
            }else
            {
                $new_file_path = CONTENT_RESOURCE_LOCAL_PATH . $new_file_path;
                $path_parts = pathinfo($new_file_path);
                $extension = @$path_parts['extension'];
                if($extension == 'zip' || $extension == 'xls')
                {
                    require_library('ExcelToText');
                    $archive_directory_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id;
                    // delete the entire existing directory and rebuild it
                    recursive_rmdir($archive_directory_path);
                    $archive_converter = new ExcelToText($new_file_path, $archive_directory_path);
                    if($archive_converter->errors())
                    {
                        echo "  <error type='fatal'>". htmlspecialchars(implode('<br/>', $archive_converter->errors()))."</error>\n";
                        break;
                    }elseif($archive_converter->is_new_schema_spreadsheet())
                    {
                        $archive_converter->convert_to_new_schema_archive();
                        if($archive_converter->errors())
                        {
                            echo "  <error type='fatal'>". htmlspecialchars(implode('<br/>', $archive_converter->errors()))."</error>\n";
                            break;
                        }
                        validate_archive($archive_directory_path);
                        break;
                    }else
                    {
                        $original_errors = $archive_converter->errors();
                        $xml_converter = new ExcelToText($new_file_path, $archive_directory_path .".xml");
                        if($xml_converter->is_old_schema_spreadsheet())
                        {
                            $converted_file_path = $xml_converter->convert_to_old_schema_xml();
                            if($xml_converter->errors())
                            {
                                echo "  <error type='fatal'>". htmlspecialchars(implode('<br/>', $xml_converter->errors()))."</error>\n";
                                break;
                            }
                            $validator = new SchemaValidator();
                            $validation_result = $validator->validate($converted_file_path);
                            if($validation_result != "true")
                            {
                                echo "  <status>Validation failed</status>\n";
                                echo "  <error type='validation'>".htmlspecialchars(implode("<br/>", $validation_result))."</error>\n";
                            }else
                            {
                                echo "  <status>Validated</status>\n";
                            }
                            break;
                        }
                        if($archive_converter->errors())
                        {
                            echo "  <error type='fatal'>". htmlspecialchars(implode('<br/>', $archive_converter->errors()))."</error>\n";
                            break;
                        }                        
                        echo "  <error type='fatal'>Unable to determine the template of Excel file</error>\n";
                        break;
                    }
                }elseif($extension == 'xml')
                {
                    $validator = new SchemaValidator();
                    $validation_result = $validator->validate($new_file_path);
                    if($validation_result != "true")
                    {
                        echo "  <status>Validation failed</status>\n";
                        echo "  <error type='validation'>".htmlspecialchars(implode("<br/>", $validation_result))."</error>\n";
                    }else
                    {
                        echo "  <status>Validated</status>\n";
                    }
                }
            }
        }else
        {
            echo "  <error type='fatal'>Upload failed</error>\n";
        }
        break;
        
        
    case "upload_content":
        $manager = new ContentManager($server_ip);
        $new_file_path = $manager->grab_file($file_path,0,"image");
        if($new_file_path)
        {
            echo "  <file_path>$new_file_path</file_path>\n";
            if(preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})/", $new_file_path, $arr))
            {
                ContentManager::sync_to_content_servers($arr[1], $arr[2], $arr[3], $arr[4]);
            }
        }else
        {
            echo "  <error type='fatal'>Upload failed</error>\n";
        }
        break;
        
    case "admin_upload":
        $manager = new ContentManager($server_ip);
        $new_file_path = $manager->grab_file($file_path,0,"upload");
        if($new_file_path)
        {
            echo "  <file_path>$new_file_path</file_path>\n";
            if(preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})/", $new_file_path, $arr))
            {
                ContentManager::sync_to_content_servers($arr[1], $arr[2], $arr[3], $arr[4]);
            }
        }else
        {
            echo "  <error type='fatal'>Upload failed</error>\n";
        }
        break;
}

echo "</response>";

function validate_archive($archive_directory_path)
{
    $archive = new ContentArchiveReader(null, $archive_directory_path);
    $validator = new ContentArchiveValidator($archive);
    $validator->get_validation_errors();
    
    if($e = $validator->errors())
    {
        $errors_as_string = array();
        $warnings_as_string = array();
        foreach($e as $error)
        {
            $errors_as_string[] = $error->__toString();
        }
        if($w = $validator->warnings())
        {
            foreach($w as $warning)
            {
                $warnings_as_string[] = $warning->__toString();
            }
        }
        echo "  <status>Validation failed</status>\n";
        echo "  <error type='validation'>".htmlspecialchars(implode("<br>", $errors_as_string))."</error>\n";
        // if($warnings_as_string) echo "  <warning type='validation'>".htmlspecialchars(implode("<br>", $warnings_as_string))."</error>\n";
    }else
    {
        echo "  <status>Validated</status>\n";
    }
}

?>
