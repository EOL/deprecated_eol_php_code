<?php
namespace php_active_record;
/* connector for University of Alberta Museums
estimated execution time:
Partner provides the EOL XML but has some formatting problems on mediaURL and thumbnailURL
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 39;

require_library('ResourceDataObjectElementsSetting');
// $resource_path = "http://project.macs.ualberta.ca/services/eol/data.xml.gz";
// $resource_path = "https://dl.dropboxusercontent.com/u/7597512/UnivAlberta/data.xml.gz";
// $resource_path = "http://localhost/cp/UnivAlberta/data.xml.gz";
$resource_path = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/UnivAlberta/data.xml.gz";

/*
$result = $GLOBALS['db_connection']->select("SELECT accesspoint_url FROM resources WHERE id=$resource_id");
$row = $result->fetch_row();
$new_resource_path = @$row[0];
if($resource_path != $new_resource_path && $new_resource_path != '') $resource_path = $new_resource_path;
*/

print "\n processing resource:\n $resource_path \n\n"; 
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = $func->load_xml_string();
$xml = fix_url_format($xml);
$func->save_resource_document($xml);

// Functions::set_resource_status_to_harvest_requested($resource_id);
// Functions::gzip_resource_xml($resource_id); -- uncomment if you want to investigate XML version

$func->call_xml_2_dwca($resource_id, "Univ. of Alberta Museums", false); //3rd param not used anymore

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "\n\n Done processing.";

function fix_url_format($xml_string)
{
    if($xml = simplexml_load_string($xml_string)) {
        echo("\nfixing URL format " . count($xml->taxon) . "-- please wait...");
        foreach($xml->taxon as $taxon) {
            foreach($taxon->dataObject as $do) {
                if($val = $do->mediaURL)        $do->mediaURL = urldecode($val);
                if($val = $do->thumbnailURL)    $do->thumbnailURL = urldecode($val);
            }
        }
        debug("\nfixing URL format -- done.");
        return $xml->asXML();
    }
    else {
        echo "\nXML is invalid.\n";
        return $xml_string;
    }
}

?>