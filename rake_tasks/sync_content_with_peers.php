<?php
namespace php_active_record;
define('DOWNLOAD_WAIT_TIME', 0);
define('DOWNLOAD_TIMEOUT_SECONDS', 30);
include_once(dirname(__FILE__) . "/../config/environment.php");
require_library('PeerContentSynchronizer');
$mysqli =& $GLOBALS['db_connection'];

$serialized_params = @$argv[1];


$peer_site_id = 99;                 // this is temporary - will be replaced with a constant in /config/environments/production.php
$maximum_number_of_workers = 12;    // this is temporary - will be replaced with a constant in /config/environments/production.php


$sync = new PeerContentSynchronizer($peer_site_id, $maximum_number_of_workers);
if($serialized_params)
{
    $sync->download_asset_from_peer(unserialize($serialized_params));
}else
{
    $sync->initiate_master_thread();
}



?>
