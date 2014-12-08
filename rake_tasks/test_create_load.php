<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

$big_image_url = 'http://farm4.staticflickr.com/3057/2687301130_c12f33ac24_o.jpg';
$small_image_url = 'http://farm4.staticflickr.com/3057/2687301130_7874bc6fd3_s.jpg';
$content_manager = new ContentManager();
$i = 0;
while($i < 1000)
{
    $i++;
    //don't give a data_object_id to this image, so that details don't get saved on any database
    $file = $content_manager->grab_file($big_image_url, "image");
    // $file = $content_manager->grab_file($small_image_url, "image");
    echo "$i - $file\n";
    //cleanup script copied from delete_content() in test_content_manager.php
    $prefix = trim(CONTENT_LOCAL_PATH . ContentManager::cache_num2path($file));
    if(!$prefix || $prefix == '/') {
        echo "Aborting delete of $prefix*\n";
        exit();
    } else {
        foreach(glob($prefix . '*') as $filename) unlink($filename);
    }
}

?>
