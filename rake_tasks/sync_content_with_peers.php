<?php
namespace php_active_record;
// define('DOWNLOAD_WAIT_TIME', 200000);
define('DOWNLOAD_WAIT_TIME', 0);
define('DOWNLOAD_TIMEOUT_SECONDS', 30);
include_once(dirname(__FILE__) . "/../config/environment.php");
require_library('PeerContentSynchronizer');
$mysqli =& $GLOBALS['db_connection'];

$serialized_params = @$argv[1];


$sync = new PeerContentSynchronizer(99);
if($serialized_params)
{
    $sync->download_asset_from_peer(unserialize($serialized_params));
}else
{
    $sync->initiate_master_thread();
}



?>
