<?php

define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IUCNRedlistAPI');
$GLOBALS['ENV_DEBUG'] = false;



// create new _temp file
$resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . "211_temp.xml", "w+");

// start the resource file with the XML header
fwrite($resource_file, SchemaDocument::xml_header());

// query IUCN and write results to file
IUCNRedlistAPI::get_taxon_xml($resource_file);

// write the resource footer
fwrite($resource_file, SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . "211_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . "211.xml", CONTENT_RESOURCE_LOCAL_PATH . "211_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . "211_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . "211.xml");

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . "211.xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=".ResourceStatus::insert('Force Harvest')." WHERE id=211");
}


?>