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
// return;

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
echo "\nStartx [manual adjustment on some names]...\n";
/* set rating to 2 */
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $func->set_data_object_rating_on_xml_document();

$GLOBALS['ENV_DEBUG'] = true;

echo "\nStartx set_data_object_rating_on_xml_document...\n";
/* manual adjustment on some names */
$xml = str_ireplace("<dwc:ScientificName>Lontra sp.or Lutra sp.</dwc:ScientificName>", "<dwc:ScientificName>Lontra sp.</dwc:ScientificName>", $xml);
$xml = str_ireplace("<dwc:ScientificName>Sciurus igniventris_or_spadiceus</dwc:ScientificName>", "<dwc:ScientificName>Sciurus igniventris</dwc:ScientificName>", $xml);
$func->save_resource_document($xml);
//--------------

////////////////// section below added in Jun 5, 2020 - convert XML to DwCA

    echo "\nStartx gzip_resource_xml...\n";
    Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate 650.gz.xml, otherwise remain commented

    echo "\nStartx call_xml_2_dwca...\n";
    //---------------------new start
    require_library('ResourceDataObjectElementsSetting');
    $nmnh = new ResourceDataObjectElementsSetting($resource_id);
    $nmnh->call_xml_2_dwca($resource_id, "Flickr files", false); //3rd param false means it is not NMNH resource.
    //---------------------new end

    //---------------------new start convert_archive_normalized() meaning remove taxa without objects, only leave taxa with objects in final dwca
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    $func->convert_archive_normalized();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
    //---------------------new end

    /* The End */
?>