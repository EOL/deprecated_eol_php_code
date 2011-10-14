<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$path = @$argv[1];

ini_set('display_errors', true);






read_directory($path);
exit;



function read_directory($path, $level = 0)
{
    if($handle = opendir($path))
    {
        $images_to_convert = array();
        $processed_images = array();
        while(false !== ($file = readdir($handle)))
        {
            if(substr($file, 0, 1) == '.') continue;
            $this_path = $path . "/" . $file;
            if(is_dir($this_path))
            {
                echo "$this_path\n";
                read_directory($this_path, $level+1);
            }elseif(preg_match("/^([0-9]+)\.(.*)$/", $file, $arr))
            {
                static $count = 0;
                static $accepted_extensions = array('jpg' => 1, 'png' => 1, 'gif' => 1, 'tif' => 1);
                static $all_images = array();
                
                $prefix = $arr[1];
                $extension = $arr[2];
                if(@$accepted_extensions[$extension])
                {
                    $count++;
                    // if($count % 1000 == 0) echo "$prefix :: $this_path :: $path/$prefix\n";
                    $images_to_convert[$prefix] = array($this_path, $path ."/". $prefix);
                }
            }elseif(preg_match("/^([0-9]+)_(580_360|130_130)\.(.*)$/", $file, $arr))
            {
                $processed_images[$arr[1]] = 1;
            }
        }
        
        // print_r($processed_images);
        ksort($images_to_convert);
        while(list($key, $val) = each($images_to_convert))
        {
            if(isset($processed_images[$key])) continue;
            echo "$key resize_image(".$val[0].", ".$val[1].");\n";
            resize_image($val[0], $val[1]);
            //exit;
        }

        closedir($handle);
    }
}


/*

there are all extensions of files in /data/www/content on Beast:

jpg
png
gif
flv
tif
pdf
xml
html
zip
mp4
avi
wav
mov
tar.gz
mp3

*/


function resize_image($path, $prefix)
{
    $sizes = getimagesize($path);
    if(@!$sizes[1])
    {
        echo "Dimensions of $path cannot be determined\n";
        return;
    }
    
    $width = $sizes[0];
    $height = $sizes[1];
    
    // // // // reduce_original($path, $prefix);
    create_smaller_version($path, 580, 360, $prefix);
    create_smaller_version($prefix.'_580_360.jpg', 260, 190, $prefix);
    create_smaller_version($prefix.'_580_360.jpg', 98, 68, $prefix);
    create_upper_left_crop($prefix.'_580_360.jpg', $width, $height, 130, $prefix);
    create_upper_left_crop($prefix.'_580_360.jpg', $width, $height, 88, $prefix);
    
    static $count = 0;
    $count++;
    
    // if($prefix == "/data/www/content/2008/10/06/12/99994") exit;
    // if($count >= 10) exit;
    if($count % 100 == 0)
    {
        echo "$path :: $prefix\n";
        echo "$count : ". time_elapsed() ."\n";
    }
}

function reduce_original($path, $prefix)
{
    shell_exec("convert $path -strip -background white -flatten -quality 80 ".$prefix."_orig.jpg");
}

function create_smaller_version($path, $new_width, $new_height, $prefix)
{
    shell_exec("convert $path -strip -background white -flatten -quality 80 \
                    -resize ".$new_width."x".$new_height."\">\" ".$prefix."_".$new_width."_".$new_height.".jpg");
}

function create_upper_left_crop($path, $width, $height, $square_dimension, $prefix)
{
    $min = min($width, $height);
    $factor = $square_dimension / $min;
    $new_width = $width * $factor;
    $new_height = $height * $factor;
    // $width_offset = floor(($new_width - $square_dimension) / 8);
    // $height_offset = floor(($new_height - $square_dimension) / 8);
    $width_offset = 0;
    $height_offset = 0;
    
    $command = "convert $path -strip -background white -flatten -quality 80 -resize '".$new_width."x".$new_height."' \
                    -gravity NorthWest -crop ".$square_dimension."x".$square_dimension."+".$width_offset."+".$height_offset." \
                    +repage ".$prefix."_".$square_dimension."_".$square_dimension.".jpg";
    // echo $command;
    shell_exec($command);
}

/*
function object_path_prefix($ocu)
{
    return "/data/www/content/".substr($ocu,0,4)."/".substr($ocu,4,2)."/".substr($ocu,6,2)."/".substr($ocu,8,2)."/".substr($ocu,10,5);
}

function object_path($ocu)
{
    return "/data/www/content/".substr($ocu,0,4)."/".substr($ocu,4,2)."/".substr($ocu,6,2)."/".substr($ocu,8,2)."/".substr($ocu,10,5).".png";
}
*/

?>
