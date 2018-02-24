<?php
namespace php_active_record;
/* connector for Smithsonian Wild Flickr photostream
execution time: 61 hours
earliest upload: 2010-11-13
earliest datetaken: 1900-01-01 00:00:00
*/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('FlickrAPI');
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
$resource_id = 650;

/* will use generic_services.eol.org.ph for the meantime until extra hardisk is installed in Archive */
return;

// /*
$user_id = "51045845@N08"; // Smithsonian Wild's photostream - http://www.flickr.com/photos/51045845@N08
$start_year = 2001;
$max_photos_per_taxon = 20;

$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;

// create new _temp file
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

$months_to_be_broken_down = array(0 => array("year" => 2008, "month" => 4),
                                  1 => array("year" => 2008, "month" => 5),
                                  2 => array("year" => 2009, "month" => 5),
                                  3 => array("year" => 2009, "month" => 6),
                                  4 => array("year" => 2009, "month" => 7),
                                  5 => array("year" => 2009, "month" => 8),
                                  6 => array("year" => 2010, "month" => 1),
                                  7 => array("year" => 2010, "month" => 2),
                                  8 => array("year" => 2010, "month" => 3));

// query Flickr and write results to file
FlickrAPI::get_photostream_photos($auth_token, $resource_file, $user_id, $start_year, $months_to_be_broken_down, $max_photos_per_taxon);

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
// */

//--------------
/* set rating to 2 */
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $func->set_data_object_rating_on_xml_document();

/* manual adjustment on some names */
$xml = str_ireplace("<dwc:ScientificName>Lontra sp.or Lutra sp.</dwc:ScientificName>", "<dwc:ScientificName>Lontra sp.</dwc:ScientificName>", $xml);
$xml = str_ireplace("<dwc:ScientificName>Sciurus igniventris_or_spadiceus</dwc:ScientificName>", "<dwc:ScientificName>Sciurus igniventris</dwc:ScientificName>", $xml);
$func->save_resource_document($xml);
//--------------

Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n elapsed time = $elapsed_time_sec seconds          \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";

?>