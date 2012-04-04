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
    echo "$i - " . $content_manager->grab_file($big_image_url, 0, "image") . "\n";
    // echo "$i - " . $content_manager->grab_file($small_image_url, 0, "image") . "\n";
}

?>
