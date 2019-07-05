<?php
namespace php_active_record;

define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/IUCNRedlistAPI');
$GLOBALS['ENV_DEBUG'] = false;
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); //report all errors except notice and warning
$resource_id = 211;
if(!Functions::can_this_connector_run($resource_id)) return;
/*                          2016    2017
                            Aug25   Oct10
taxon =      73472  76021   81702
reference =  10065  10425   10424
synonym =    40378  43889   51115
commonName = 64921  62781   72501
texts =      452501 470233  511207
START OF DwC-A                      
agent.tab                           12591
media_resource.tab                  506828
reference.tab                       9804
taxon.tab                           129835
vernacular_name.tab                 71221

211	Friday 2019-07-05 12:01:15 PM	{"agent.tab":12591,"media_resource.tab":506828,"reference.tab":9804,"taxon.tab":129834,"vernacular_name.tab":77416}
*/

// create new _temp file
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

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
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

//fix bad xml char
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$xml_string = Functions::get_remote_file($resource_path);
$xml_string = Functions::remove_invalid_bytes_in_XML($xml_string);
if(!($WRITE = Functions::file_open($resource_path, "w"))) return;
fwrite($WRITE, $xml_string);
fclose($WRITE);


require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "IUCN files", false); //3rd param false means it is not NMNH resource.

?>
