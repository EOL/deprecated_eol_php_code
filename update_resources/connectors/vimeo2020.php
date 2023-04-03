<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
/*
214	Thu 2020-10-15 10:44:09 AM	{"agent.tab":22, "media_resource.tab":290, "taxon.tab":208, "vernacular_name.tab":22, "time_elapsed":{"sec":472.48, "min":7.87, "hr":0.13}}
214	Tue 2023-03-28 07:01:10 AM	{"agent.tab":22, "media_resource.tab":290, "taxon.tab":208, "vernacular_name.tab":22, "time_elapsed":{"sec":474.38, "min":7.91, "hr":0.13}}
214	Thu 2023-03-30 06:52:13 AM	{"agent.tab":22, "media_resource.tab":290, "taxon.tab":208, "vernacular_name.tab":22, "time_elapsed":{"sec":485.66, "min":8.09, "hr":0.13}}


Steps on how Vimeo users can share their videos to eol.org:

1. Add a "taxonomic tag"
It can be as simple as:
[taxonomy:binomial=Anarhichas lupus]

Or as complete as:
[taxonomy:binomial=Anarhichas lupus]
[taxonomy:kingdom=Animalia]
[taxonomy:phylum=Chordata]
[taxonomy:class=Actinopterygii]
[taxonomy:order=Perciformes]
[taxonomy:family=Anarhichadidae]
[taxonomy:common=Atlantic wolffish]

Two places where you can put your "taxonomic tag":
1. You can add this in the description section.
Settings - General - Info - Description
Below your actual description of your video.

2. Or you can add it as tags:
Settings - Distribution - Discovery - Tags

2. Choose a Creative Commons License
Settings - Distribution - Discovery - Creative Commons license

Choose any of these:
- Attribution
- Attribution Share Alike
- Attribution Non-Commercial
- Attribution Non-Commercial Share Alike

3. Make sure your photo is public
Settings - General - Privacy - Who can watch? - Anyone

4. Share your photo with the "Encyclopedia of Life Videos" group.
Settings - General - Privacy - Groups
Add group "Encyclopedia of Life Videos".

*/

$timestart = time_elapsed();
$resource_id = 214;
// /* main - normal operation
require_library('connectors/VimeoAPI2020');
$func = new VimeoAPI2020($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means to delete working resource folder
// exit;
// */

/* API test working OK
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
                              /users/83097635/videos
print_r($response); exit("\n");
// */

/* works on parsing out the media URL, an mp4 for that matter!
$url = 'https://player.vimeo.com/video/19082391';
// $url = 'https://player.vimeo.com/video/19083211';
$options['expire_seconds'] = 60*50; //1 hr
$html = Functions::lookup_with_cache($url, $options);
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
?>