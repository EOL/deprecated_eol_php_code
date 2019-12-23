<?php
namespace php_active_record;
/* Andreas Kay photostream - https://www.flickr.com/photos/andreaskay/ (DATA-1843)
execution time (Jenkins initial):
execution time (Jenkins suceeding):

Note: this is a template copied from 544.php

Interestingly this: https://www.flickr.com/photos/andreaskay/
  is synonymous to: https://www.flickr.com/photos/75374522@N06/
*/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('FlickrAPI');
$timestart = time_elapsed();

$temp = CONTENT_RESOURCE_LOCAL_PATH . "/reports";
if(!is_dir($temp)) mkdir($temp);

$GLOBALS['ENV_DEBUG'] = false;
$resource_id = 'andreas_kay_flickr';

// /* start main block
$user_id = "75374522@N06"; //Andreas Kay photostream - https://www.flickr.com/photos/andreaskay/ OR https://www.flickr.com/photos/75374522@N06/
$start_year = 2011; //Andreas Kay joined Flickr 2012. Less 1 to get 2011, just in case.

// /* seems can be commented. Just a check if FLICKR_AUTH_TOKEN is a valid token, which it is. So why bother.
$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;
// */

/* ---------- start test*** ----------
// exit("\n".FLICKR_AUTH_TOKEN."\n");
$auth_token = FLICKR_AUTH_TOKEN; //72157606690941918-97c1c060a2d18b5b
FlickrAPI::get_photostream_photos($auth_token, NULL, $user_id, NULL, NULL, NULL, $resource_id);

$photo_id = 48862446481; //option 1 & 2
$photo_id = 48861925383; //option 3
// $photo_id = 48850307862; //with binomial with '?' and another good binomial
$photo_id = 6947028345; //weird error
$photo = FlickrAPI::photos_get_info($photo_id, "0ab954923d");
$p = $photo->photo;
print_r($p);
// echo "\n".$photo->photo->id;
// exit("\nelix\n");

$photo_id = 48862446481;
$photo_id = $p->id;
$photo = FlickrAPI::get_taxa_for_photo($photo_id, "0ab954923d", $p->dates->lastupdate, NULL, '75374522@N06');
// $p = $photo->photo;
// print_r($p);
// echo "\n".$photo->photo->id;
exit("\n-end test here-\n");
---------- end ---------- */

// create new _temp file
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

// query Flickr and write results to file
/* copied from template
$months_to_be_broken_down = array(0 => array("year" => 2011, "month" => 8),
                                  1 => array("year" => 2011, "month" => 10),
                                  2 => array("year" => 2012, "month" => 8),
                                  3 => array("year" => 2012, "month" => 11),
                                  4 => array("year" => 2013, "month" => 1),
                                  5 => array("year" => 2013, "month" => 3));
*/
$months_to_be_broken_down = array(); //for Andreas Kay, so far.
FlickrAPI::get_photostream_photos($auth_token, $resource_file, $user_id, $start_year, $months_to_be_broken_down, NULL, $resource_id);

print_r(@$GLOBALS['func']->count);

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
// exit("\nstop here first\n");

/* not needed for Andreas Kay, this line is from copied template
remove_bhl_images_already_existing_in_eol_group($resource_id);
*/
Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate andreas_kay_flickr.xml.gz, otherwise remain commented

//---------------------new start
require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "Flickr files", false); //3rd param false means it is not NMNH resource.
//---------------------new end

// end main block */

//---------------------new start generic_normalize_dwca() meaning remove taxa without objects, only leave taxa with objects in final dwca
require_library('connectors/DwCA_Utility');
$func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
$func->convert_archive_normalized();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
//---------------------new end

//---------------------start: make rows in report unique.
require_library('connectors/CachingTemplateAPI_AndreasKay');
$func = new CachingTemplateAPI_AndreasKay($resource_id);
$func->make_unique_rows();
//---------------------end

/* The End */
?>