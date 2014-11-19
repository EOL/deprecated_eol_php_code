<?php
namespace php_active_record;

$data_object_id = @$argv[1];
$thumbnail = @$argv[2];

if(!$data_object_id || !is_numeric($data_object_id))
{
    echo "\n\n\tforce_download.php data_object_id [thumbnail]\n\n";
    exit;
}



include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$result = $mysqli->query("SELECT id, object_url, thumbnail_url FROM data_objects WHERE id=$data_object_id LIMIT 1");
if($result && $row=$result->fetch_assoc())
{
    print_r($row);
    $content_manager = new ContentManager();
    if($thumbnail == 'thumbnail')
    {
        if($row['thumbnail_url'])
        {
            if($new_object_cache_url = $content_manager->grab_file($row["thumbnail_url"], "image"))
            {
                //echo "UPDATE data_objects SET object_cache_url=$object_cache_url WHERE object_cache_url=$object_cache_url";
                $mysqli->query("UPDATE data_objects SET thumbnail_cache_url=$new_object_cache_url WHERE id=$data_object_id");
                
                $y = date("Y");
                $m = date("m");
                $d = date("d");
                $h = date("H");
                $r = substr($new_object_cache_url, -5);
                echo "\n\nDone: http://content1.eol.org/content/$y/$m/$d/$h/$r" . "_orig.jpg\n\n";
            }
        }
    }else
    {
        //must impose old crop locations on the new image to grab
        $crop = $mysqli->query("SELECT crop_x_pct, crop_y_pct, crop_width_pct, crop_height_pct FROM image_sizes WHERE data_object_id=$data_object_id LIMIT 1");
        if($crop && $row=$crop->fetch_row()) {
            $options = array('crop_pct' => $row, 'data_object_id' = $data_object_id);
        } else $options = NULL;
        if($new_object_cache_url = $content_manager->grab_file($row["object_url"], "image", $options)))
        {
            //echo "UPDATE data_objects SET object_cache_url=$object_cache_url WHERE object_cache_url=$object_cache_url";
            $mysqli->query("UPDATE data_objects SET object_cache_url=$new_object_cache_url WHERE id=$data_object_id");
            
            $y = date("Y");
            $m = date("m");
            $d = date("d");
            $h = date("H");
            $r = substr($new_object_cache_url, -5);
            echo "\n\nDone: http://content1.eol.org/content/$y/$m/$d/$h/$r" . "_orig.jpg\n\n";
        }
    }
}




?>
