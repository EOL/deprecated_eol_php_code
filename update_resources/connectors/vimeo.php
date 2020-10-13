<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "protisten";

/* un-comment in real operation
require_library('connectors/Protisten_deAPI');
$func = new Protisten_deAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param true means to delete working resource folder
*/

// /* API test working OK
require DOC_ROOT.'/vendor/vimeo_api/vendor/autoload.php';
use Vimeo\Vimeo;
$client_id = '8498d03ee2e3276f878fbbeb2354a1552bfea767';
$client_secret = '579812c7f9e9cef30ab1bf088c3d3b92073e115c';
$access_token = 'be68020e45bf5677e69034c8c2cfc91b';
$client = new Vimeo("$client_id", "$client_secret", "$access_token");
// $response = $client->request('/tutorial', array(), 'GET');
// $response = $client->request('/groups/encyclopediaoflife', array(), 'GET');
// $response = $client->request('/groups/77006/users', array(), 'GET');
// $response = $client->request('/users/5814509', array(), 'GET'); //Katja
$response = $client->request('/users/5814509/videos', array(), 'GET'); //Katja's videos
print_r($response); exit("\n");
// */

/* works on parsing out the media URL, an mp4 for that matter!
$url = 'https://player.vimeo.com/video/19082391';
$url = 'https://player.vimeo.com/video/19083211';
$html = Functions::lookup_with_cache($url);
// "mime":"video/mp4","fps":29,"url":"https://vod-progressive.akamaized.net/exp=1602601456~acl=%2A%2F38079480.mp4%2A~hmac=92351066b44bf9ac9dffafa207e1bc60f68f42ddb7a283938ae650a3bde2c8e8/vimeo-prod-skyfire-std-us/01/3816/0/19082391/38079480.mp4","cdn"
if(preg_match("/\"mime\":\"video\/mp4\"(.*?)\.mp4\"/ims", $html, $arr)) {
    $str = $arr[1];
    echo "\n$str\n";
    // ,"fps":29,"url":"https://vod-progressive.akamaized.net/exp=1602601908~acl=%2A%2F38079480.mp4%2A~hmac=1853127a5ec9959d6be10883146d0a544bf19d7e1834d2168dd239bb54900050/vimeo-prod-skyfire-std-us/01/3816/0/19082391/38079480
    $str .= '.mp4 xxx';
    if(preg_match("/https\:\/\/(.*?) xxx/ims", $str, $arr)) {
        $str = $arr[1];
        echo "\n$str\n";
    }
}
else exit("\nInvestigate: no mp4!\n");
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>