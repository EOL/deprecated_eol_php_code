<?php
namespace php_active_record;

define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IUCNRedlistAPI');
$GLOBALS['ENV_DEBUG'] = false;
$resource_id = 211;
/*
taxon =      73472  76021
reference =  10065  10425
synonym =    40378  43889
commonName = 64921  62781
texts =      452501 470233
*/

// create new _temp file
if(!($resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml");
  return;
}

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

// query IUCN and write results to file
// IUCNRedlistAPI::get_taxon_xml($resource_file); //using partnet-hosted json dump file

$func = new IUCNRedlistAPI();
$func->get_taxon_xml($resource_file, "IUCN using .csv export");

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600)
{
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::gzip_resource_xml($resource_id);
}
?>