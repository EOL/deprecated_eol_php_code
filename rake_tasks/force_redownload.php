<?php
namespace php_active_record;

$data_object_id = @$argv[1];

if(!$data_object_id || !is_numeric($data_object_id))
{
    echo "\n\n\tforce_download.php data_object_id\n\n";
    exit;
}



include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$result = $mysqli->query("SELECT id, object_url FROM data_objects WHERE id=$data_object_id LIMIT 1");
if($result && $row=$result->fetch_assoc())
{
    $content_manager = new ContentManager();
    if($new_object_cache_url = $content_manager->grab_file($row["object_url"], 0, "image", CONTENT_IMAGE_LARGE))
    {
        //echo "UPDATE data_objects SET object_cache_url=$object_cache_url WHERE object_cache_url=$object_cache_url";
        $mysqli->query("UPDATE data_objects SET object_cache_url=$new_object_cache_url WHERE id=$data_object_id");
        
        $y = date("Y");
        $m = date("m");
        $d = date("d");
        $h = date("H");
        // ContentManager::sync_to_content_servers($y, $m, $d, $h);
        
        $r = substr($new_object_cache_url, -5);
        echo "\n\nDone: http://content1.eol.org/content/$y/$m/$d/$h/$r" . "_large.jpg\n\n";
    }
}




?>