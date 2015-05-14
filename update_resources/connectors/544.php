<?php
namespace php_active_record;
/* connector for BHL BioDivLibrary's photostream -- http://www.flickr.com/photos/61021753@N02
execution time: 18 hours
*/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('FlickrAPI');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false;

$resource_id = 544;
$user_id = "61021753@N02"; // BHL BioDivLibrary's photostream -- http://www.flickr.com/photos/61021753@N02
$start_year = 2010;

$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;

// create new _temp file
if(!($resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml");
  return;
}

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

// query Flickr and write results to file

$months_to_be_broken_down = array(0 => array("year" => 2011, "month" => 8),
                                  1 => array("year" => 2011, "month" => 10),
                                  2 => array("year" => 2012, "month" => 8),
                                  3 => array("year" => 2012, "month" => 11),
                                  4 => array("year" => 2013, "month" => 1),
                                  5 => array("year" => 2013, "month" => 3));
FlickrAPI::get_photostream_photos($auth_token, $resource_file, $user_id, $start_year, $months_to_be_broken_down);
// FlickrAPI::get_all_eol_photos($auth_token, $resource_file, $user_id); // old

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
@rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

Functions::set_resource_status_to_force_harvest($resource_id);
remove_bhl_images_already_existing_in_eol_group($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";


function remove_bhl_images_already_existing_in_eol_group($resource_id)
{
    $file = "http://dl.dropbox.com/u/7597512/BHL_images/BHL_images_in_EOLGroup.txt";
    // $file = "http://localhost/~eolit/eli/eol_php_code/update_resources/connectors/files/BHL_images/BHL_images_in_EOLGroup_sample.txt";
    $contents = Functions::get_remote_file($file, array('timeout' => 600, 'download_attempts' => 5));
    $do_ids = json_decode($contents,true);
    print "\n\n from text file: " . count($do_ids);
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    $xml_string = Functions::get_remote_file($resource_path, array('timeout' => 240, 'download_attempts' => 5));
    $xml = simplexml_load_string($xml_string);
    $i = 0;
    $deleted_ids = array();
    $deleted = 0;
    foreach($xml->taxon as $taxon)
    {
        $i++;
        $dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        echo "\n[" . $dwc->ScientificName."]";
        $j = 0;
        $deleted_do_keys = array();
        foreach($taxon->dataObject as $do)
        {
            $j++;
            $dc2 = $do->children("http://purl.org/dc/elements/1.1/");
            $do_id = trim($dc2->identifier);
            if(in_array($do_id, $do_ids))
            {
                $deleted++;
                $deleted_ids[$do_id] = 1;
                print "\n --- deleting $do_id";
                $deleted_do_keys[] = $j-1;
            }
        }
        foreach($deleted_do_keys as $key)
        {
            unset($xml->taxon[$i-1]->dataObject[$key]);
        }
    }
    print "\n\n occurrence do_ids: $i";
    print "\n\n deleted <dataObject>s: $deleted";
    print "\n\n deleted unique do_ids: " . count($deleted_ids);
    $xml_string = $xml->asXML();
    require_library('ResourceDataObjectElementsSetting');
    $xml_string = ResourceDataObjectElementsSetting::delete_taxon_if_no_dataObject($xml_string);
    if(!($WRITE = fopen($resource_path, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    fwrite($WRITE, $xml_string);
    fclose($WRITE);
}

function bhl_image_count() // just for stats
{
    $path = "http://localhost/~eolit/eli/eol_php_code/applications/content_server/resources/544.xml";
    $path = "http://localhost/~eolit/eli/eol_php_code/applications/content_server/resources/544%20BHL%20in%20EOL%20Flickr%20Group.xml";
    print "\n xml file: [$path] \n";
    $reader = new \XMLReader();
    $reader->open($path);
    $i = 0;
    $do_ids = array();
    $names = array();
    while(@$reader->read())
    {
        if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
        {
            $string = $reader->readOuterXML();
            $taxon = simplexml_load_string($string);
            $t_dc = $taxon->children("http://purl.org/dc/elements/1.1/");
            $t_dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
            $family         = trim($t_dwc->Family);
            $genus          = trim($t_dwc->Genus);
            $scientificname = trim($t_dwc->ScientificName);
            $taxon_identifier = trim($t_dc->identifier);
            $i++;
            print "\n $i. $scientificname [$taxon_identifier]";
            $names[$scientificname] = 1;
            foreach($taxon->dataObject as $do)
            {
                $t_dc2 = $do->children("http://purl.org/dc/elements/1.1/");
                $id = trim($t_dc2->identifier);
                $do_ids[$id] = 1;
            }
        }
    }
    $names = array_keys($names);
    print "\n total names: " . count($names);
    $do_ids = array_keys($do_ids);
    print "\n total do: " . count($do_ids);
    
    $filename = "BHL_images_in_EOLGroup.txt";
    if(!($WRITE = fopen($filename, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$filename);
      return;
    }
    fwrite($WRITE, json_encode($do_ids));
    fclose($WRITE);

    // just testing - reading it back
    if(!($READ = fopen($filename, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$filename);
      return;
    }
    $contents = fread($READ, filesize($filename));
    fclose($READ);
    $do_ids = json_decode($contents,true);
    print "\n\n from text file: " . count($do_ids);
}

?>
