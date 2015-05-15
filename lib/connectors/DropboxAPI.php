<?php
namespace php_active_record;
define("DROPBOX_ACCESS_TOKEN", "0L_P2JHHe60AAAAAAAARGn8Au3W0IEmAHbWgHQzSfyP_QMvomhOkuHc-ATnbb23Z");
require_once "vendor/dropbox-sdk-php/lib/Dropbox/autoload.php";
use \Dropbox as dbx;

class DropboxAPI
{
    function upload_file_to_dropbox($params = array())
    {
        if(!$params) return;
        if(!($filename = pathinfo($params['source'], PATHINFO_BASENAME))) return;
        /* copy file to Dropbox */
        echo "\naccessing Dropbox...\n";
        if(!($dbxClient = new dbx\Client(@$params['dropbox_access_token'], "PHP-Example/1.0"))) return;
        /* $accountInfo = $dbxClient->getAccountInfo(); print_r($accountInfo); */
        if($f = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $filename, "rb"))
        {
            if($info = $dbxClient->getMetadata($params['dropbox_path'].$filename))
            {
                $dbxClient->delete($params['dropbox_path'].$filename);
                echo "\nexisting file deleted\n";
            }
            else echo "\nfile does not exist yet\n";
            if($info = $dbxClient->uploadFile($params['dropbox_path'].$filename, dbx\WriteMode::add(), $f))
            {
                echo "\nfile uploaded OK\n";
                return true;
            }
            else echo "\nfile not uploaded!\n";
            fclose($f);
        }
        return false;
    }
}
?>