<?php
namespace php_active_record;
/* connector for BHL BioDivLibrary's photostream -- http://www.flickr.com/photos/61021753@N02
execution time: 18 hours
------------------------------
execution time (Jenkins initial): 23 hours
execution time (Jenkins suceeding): 2 min 8 sec
                                             7-Oct
http://eol.org/schema/agent/agent:           1
http://purl.org/dc/dcmitype/StillImage:      19103
http://rs.tdwg.org/dwc/terms/taxon:          20654
http://rs.gbif.org/terms/1.0/vernacularname: 4879

544	Saturday 2017-10-07 09:33:30 PM	{"agent.tab":1,"media_resource.tab":19103,"taxon.tab":20654,"vernacular_name.tab":4879}
544	Thursday 2017-10-19 10:49:56 PM	{              "media_resource.tab":13654,"taxon.tab":12554,"vernacular_name.tab":2610}
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
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

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
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

remove_bhl_images_already_existing_in_eol_group($resource_id);
Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate 544.gz.xml, otherwise remain commented

//---------------------new start
require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "Flickr files", false); //3rd param false means it is not NMNH resource.
//---------------------new end

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";


function remove_bhl_images_already_existing_in_eol_group($resource_id)
{
    // $file = "http://localhost/cp_new/BHL/BHL_images/BHL_images_in_EOLGroup.txt";
    // $file = "http://dl.dropbox.com/u/7597512/BHL_images/BHL_images_in_EOLGroup.txt"; //can no longer be accessed publicly. But file is still there in dropbox.
    $file = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/BHL/BHL_images/BHL_images_in_EOLGroup.txt";
    $contents = Functions::get_remote_file($file, array('cache' => 0, 'timeout' => 600, 'download_attempts' => 5));
    $do_ids = json_decode($contents,true);
    print "\n\n from text file: " . count($do_ids);
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    $xml_string = Functions::get_remote_file($resource_path, array('timeout' => 240, 'download_attempts' => 5));
    $xml_string = Functions::remove_invalid_bytes_in_XML($xml_string);
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
    if(!($WRITE = Functions::file_open($resource_path, "w"))) return;
    fwrite($WRITE, $xml_string);
    fclose($WRITE);
}

function bhl_image_count() // just for stats
{
    $path = "http://localhost/eol_php_code/applications/content_server/resources/544.xml";
    $path = "http://localhost/eol_php_code/applications/content_server/resources/544%20BHL%20in%20EOL%20Flickr%20Group.xml";
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
    if(!($WRITE = Functions::file_open($filename, "w"))) return;
    fwrite($WRITE, json_encode($do_ids));
    fclose($WRITE);

    // just testing - reading it back
    if(!($READ = Functions::file_open($filename, "r"))) return;
    $contents = fread($READ, filesize($filename));
    fclose($READ);
    $do_ids = json_decode($contents,true);
    print "\n\n from text file: " . count($do_ids);
}

?>
