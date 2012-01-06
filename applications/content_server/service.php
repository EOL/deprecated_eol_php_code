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
            
            ContentManager::sync_partner_logos();
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
        
        $manager = new ContentManager($server_ip);
        $new_file_path = $manager->grab_file($file_path,$resource_id,"resource");
        if($new_file_path)
        {
            $validator = new SchemaValidator();
            $validation_result = $validator->validate(CONTENT_RESOURCE_LOCAL_PATH.$new_file_path);
            if($validation_result!="true")
            {
                echo "  <status>Validation failed</status>\n";
                echo "  <error type='validation'>".htmlspecialchars(implode("<br>", $validation_result))."</error>\n";
            }else
            {
                echo "  <status>Validated</status>\n";
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

?>
