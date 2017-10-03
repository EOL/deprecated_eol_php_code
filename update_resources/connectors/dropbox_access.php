<?php

# Include the Dropbox SDK libraries
require_once "vendor/dropbox-sdk-php-1.1.5/lib/Dropbox/autoload.php";
use \Dropbox as dbx;

// $appInfo = dbx\AppInfo::loadFromJsonFile(dirname(__FILE__) . "/../../vendor/dropbox-sdk-php-1.1.5/lib/Dropbox/key_secret.app");
// $webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
// $authorizeUrl = $webAuth->start();

// echo "1. Go to: " . $authorizeUrl . "\n";
// echo "2. Click \"Allow\" (you might have to log in first).\n";
// echo "3. Copy the authorization code.\n";
// $authCode = \trim(\readline("Enter the authorization code here: "));

// $authCode = "0L_P2JHHe60AAAAAAAARG5GxV1T8uEtjfWn_nY7aTIw";
// 
// list($accessToken, $dropboxUserId) = $webAuth->finish($authCode);
// print "Access Token: " . $accessToken . "\n";


$accessToken = "0L_P2JHHe60AAAAAAAARGn8Au3W0IEmAHbWgHQzSfyP_QMvomhOkuHc-ATnbb23Z";
$dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
// $accountInfo = $dbxClient->getAccountInfo();
// print_r($accountInfo);

//upload a file
$f = fopen(dirname(__FILE__) . "/../../update_resources/connectors/db-test1.php", "rb");
if($result = $dbxClient->uploadFile("/Public/iNaturalist/db-test1.txt", dbx\WriteMode::add(), $f))
{
    echo "\nfile uploaded\n";
    print_r($result);
}
else echo "\nerror\n";
fclose($f);

// $folderMetadata = $dbxClient->getMetadataWithChildren("/");
// print_r($folderMetadata);

?>

