<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

/*
$content_manager = new ContentManager();
$f = "http://ww2.bgbm.org/herbarium/images/B/10/00/68/95/B_10_0068953.jpg";
$f = "/Volumes/Eli blue/BotanicalEuropeana/BotanicalEuropeana_0/B_10_0154752.jpg";
if($new_object_cache_url = $content_manager->grab_file($f, "image"))
{
    echo "\n [$new_object_cache_url]";
}
*/

$content_manager = new ContentManager();
$path = "\/Volumes\/Eli\ blue\/BotanicalEuropeana\/BotanicalEuropeana_0\/B_10_0154752.jpg";
$path = "\/Volumes\/Eli\ blue\/BotanicalEuropeana\/BotanicalEuropeana_20\/B_-W_11629%20-01%200.jpg";
$path = DOC_ROOT . "temp/B_10_0154682.jpg";
// $destination_path = "\/Volumes\/Eli\ blue\/BotanicalEuropeana_test\/";
$destination_path = DOC_ROOT . "temp/";

$prefix = "B_10_0154682";
// $dim = array(800, 580);
// $dim = array(10300, 10080);
$dim = array(1300, 1080); // Jen's choice

$x = $content_manager->create_smaller_version($path, $dim, $destination_path.$prefix, implode($dim, '_'));
echo "\n[$x]\n";
?>
