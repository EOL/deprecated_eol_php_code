<?php
namespace php_active_record;
/*connector for iNaturalist: DATA-1594 reverse connector, EOL data back to iNat.
This doesn't create a resource for ingestion but rather a text file of URLs to be given back to the partner
estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/INaturalistAPI');
$func = new INaturalistAPI();
$func->generate_link_backs();

/* copy file to Dropbox */
require_once "vendor/dropbox-sdk-php-1.1.5/lib/Dropbox/autoload.php";
use \Dropbox as dbx;
$accessToken = "0L_P2JHHe60AAAAAAAARGn8Au3W0IEmAHbWgHQzSfyP_QMvomhOkuHc-ATnbb23Z";
$dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
/* $accountInfo = $dbxClient->getAccountInfo(); print_r($accountInfo); */
$file = "iNat_EOL_object_urls.txt.zip";
$dropbox_path = "/Public/iNaturalist/";
$f = fopen(CONTENT_RESOURCE_LOCAL_PATH . $file, "rb");
if($result = $dbxClient->getMetadata($dropbox_path.$file))
{
    print_r($result);
    $dbxClient->delete($dropbox_path.$file);
    echo "\nexisting file deleted\n";
}
else echo "\nfile does not exist yet\n";
$result = $dbxClient->uploadFile($dropbox_path.$file, dbx\WriteMode::add(), $f);
echo "\nfile uploaded\n";
print_r($result);
fclose($f);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\nelapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>