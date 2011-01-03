<?php

define('DOWNLOAD_WAIT_TIME', '500000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IUCNRedlistAPI');
$GLOBALS['ENV_DEBUG'] = false;




// // create new _temp file
// $resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . "9999.xml", "w+");

// query Flickr and write results to file
$xml = IUCNRedlistAPI::get_taxon_xml();

// // write the resource footer
// fwrite($resource_file, $xml);
// fclose($resource_file);
// 
// // set Flickr to force harvest
// if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "9999.xml"))
// {
//     $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert('Force Harvest')." WHERE id=15");
// }

?>