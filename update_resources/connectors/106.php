<?php
namespace php_active_record;
/*connector for The Biodiversity of Tamborine Mountain
estimated execution time: 
Partner provides a list of URL's for its individual species XML.
The connector loops to this list and compiles each XML to 1 final XML for EOL ingestion.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/ConabioAPI');
$resource_id = 106;
$func = new ConabioAPI();
$func->combine_all_xmls($resource_id);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";

// /* working well - replaces Class='Insecta' to 'Reptilia' if Order=='Squamata' --- WEB-5509
require_library('ResourceDataObjectElementsSetting');
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = file_get_contents($resource_path);
$xml = $func->replace_taxon_element_value_with_condition("dwc:Class", "Insecta", "Reptilia", $xml, "dwc:Order", "Squamata");
$func->save_resource_document($xml);
// */

// start - this will get Tamborines videos from Vimeo and append it with the main resource 106.xml (DATA-1592)
Functions::file_rename($resource_path, CONTENT_RESOURCE_LOCAL_PATH . "temp_vimeo_to_tamborine1.xml");
get_videos_from_vimeo();
Functions::combine_all_eol_resource_xmls($resource_id, CONTENT_RESOURCE_LOCAL_PATH . "temp_vimeo_to_tamborine*.xml");
unlink(CONTENT_RESOURCE_LOCAL_PATH . "temp_vimeo_to_tamborine1.xml");
unlink(CONTENT_RESOURCE_LOCAL_PATH . "temp_vimeo_to_tamborine2.xml");
// end

if(filesize($resource_path) > 1000)
{
    Functions::set_resource_status_to_harvest_requested($resource_id);
    Functions::gzip_resource_xml($resource_id);
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";

function get_videos_from_vimeo()
{
    echo "\n -- start access to vimeo ";
    $resource_id = "temp_vimeo_to_tamborine2";
    require_library('connectors/VimeoAPI');
    $taxa = VimeoAPI::get_all_taxa(array("user1632860")); // Peter Kuttner's id
    $xml = \SchemaDocument::get_taxon_xml($taxa);
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    if(!($OUT = fopen($resource_path, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    fwrite($OUT, $xml);
    fclose($OUT);
    echo " -- end.\n";
}
?>