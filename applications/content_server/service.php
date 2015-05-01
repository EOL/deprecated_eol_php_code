<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$function = @$_GET["function"];
$server_ip = @$_GET["server_ip"];
$file_path = @$_GET["file_path"];
$agent_id = @$_GET["agent_id"];
$resource_id = @$_GET["resource_id"];
$amount = @$_GET["amount"];
$currecny = @$_GET["currecny"];
$type = @$_GET["type"];
$data_object_id = @$_GET["data_object_id"];
$data_search_file_id = @$_GET["data_search_file_id"];
$x = @$_GET["x"];
$y = @$_GET["y"];
$w = @$_GET["w"];
$h = @$_GET["h"];

if(!$function) $function = @$_POST["function"];
if(!$server_ip) $server_ip = @$_POST["server_ip"];
if(!$file_path) $file_path = @$_POST["file_path"];
if(!$agent_id) $agent_id = @$_POST["agent_id"];
if(!$resource_id) $resource_id = @$_POST["resource_id"];
if(!$amount) $amount = @$_POST["amount"];
if(!$currecny) $currecny = @$_POST["currecny"];
if(!$type) $type = @$_POST["type"];
if(!$data_object_id) $data_object_id = @$_POST["data_object_id"];
if(!$x) $x = @$_POST["x"];
if(!$y) $y = @$_POST["y"];
if(!$w) $w = @$_POST["w"];


header('Content-type: text/xml');
echo "<?xml version='1.0' encoding='UTF-8'?>\n\n";
echo "<response>\n";

switch($function)
{
    case "partner_image":
        $manager = new ContentManager();
        $new_file_path = $manager->grab_file($file_path, "partner");
        if($new_file_path)
        {
            if(preg_match("/^([0-9]+)\./", $new_file_path, $arr)) $new_file_path = $arr[1];
            echo "  <file_prefix>$new_file_path</file_prefix>\n";
        }else echo "  <error type='fatal'>Upload failed</error>\n";
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
        $resource = Resource::find($resource_id);
        if(!$resource || !$resource->exists())
        {
            echo "  <error type='fatal'>Invalid resource_id included</error>\n";
            break;
        }
        // remove:
        //      resources/ID/*
        //      resources/ID
        //      resources/ID.*
        wildcard_rm(CONTENT_RESOURCE_LOCAL_PATH . $resource_id);

        $manager = new ContentManager();
        $new_file_path = $manager->grab_file($file_path, "resource", array('resource_id' => $resource_id));
        if($new_file_path)
        {
            if(is_dir($new_file_path))
            {
                if($xml_file = Functions::get_single_xml_file_in_directory($new_file_path))
                {
                    rename($xml_file, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
                    recursive_rmdir($new_file_path);
                    $new_file_path = $resource_id . ".xml";
                }
            }

            if(is_dir($new_file_path))
            {
                validate_archive($new_file_path, $resource);
            }else
            {
                $new_file_path = CONTENT_RESOURCE_LOCAL_PATH . $new_file_path;
                $path_parts = pathinfo($new_file_path);
                $extension = @$path_parts['extension'];
                if($extension == 'xls' || $extension == 'xlsx')
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
                        validate_archive($archive_directory_path, $resource);
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
                                if(strlen($validation_result) > 50000) $validation_result = substr($validation_result, 0, 50000) . "...";
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
                        $validation_message = implode("<br/>", $validation_result);
                        if(strlen($validation_message) > 50000) $validation_message = substr($validation_message, 0, 50000) . "...";
                        echo "  <error type='validation'>".htmlspecialchars($validation_message)."</error>\n";
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
        $manager = new ContentManager();
        $new_file_path = $manager->grab_file($file_path, "image", array('data_object_id' => $data_object_id));
        if($new_file_path) echo "  <file_path>$new_file_path</file_path>\n";
        else echo "  <error type='fatal'>Upload failed</error>\n";
        break;

    case "admin_upload":
        $manager = new ContentManager();
        $new_file_path = $manager->grab_file($file_path, "upload");
        if($new_file_path) echo "  <file_path>$new_file_path</file_path>\n";
        else echo "  <error type='fatal'>Upload failed</error>\n";
        break;

    case "upload_dataset":
        if(!$data_search_file_id)
        {
            echo "  <error type='fatal'>No data_search_file_id included</error>\n";
            break;
        }
        if(!$file_path)
        {
            echo "  <error type='fatal'>No file_path included</error>\n";
            break;
        }
        $manager = new ContentManager();
        if($data_search_file_id) $new_file_path = $manager->grab_file($file_path, "dataset", array('data_search_file_id' => $data_search_file_id));
        if($new_file_path) echo "  <file_path>$new_file_path</file_path>\n";
        else echo "  <error type='fatal'>Upload failed</error>\n";
        break;

    case "crop_image":
        $manager = new ContentManager();
        $new_file_path = $manager->crop_image($data_object_id, $x, $y, $w);
        if($new_file_path) echo "  <file_path>$new_file_path</file_path>\n";
        else echo "  <error type='fatal'>Upload failed</error>\n";
        break;

    case "crop_image_pct":
        $manager = new ContentManager();
        $new_file_path = $manager->crop_image_pct($data_object_id, $x, $y, $w, $h);
        if($new_file_path) echo "  <file_path>$new_file_path</file_path>\n";
        else echo "  <error type='fatal'>Upload failed</error>\n";
        break;
}

echo "</response>";

function validate_archive($archive_directory_path, $resource)
{
    $archive = new ContentArchiveReader(null, $archive_directory_path);
    $validator = new ContentArchiveValidator($archive, $resource);
    if($validator->is_valid()) echo "  <status>Validated</status>\n";
    else echo "  <status>Validation failed</status>\n";

    $errors = array_merge($validator->structural_errors(), $validator->display_errors());
    $errors_as_string = null;
    foreach($errors as $error)
    {
        $errors_as_string[] = $error->__toString();
    }
    if($errors_as_string) echo "  <error type='validation'>".htmlspecialchars(implode("<br>", $errors_as_string))."</error>\n";
}

?>
