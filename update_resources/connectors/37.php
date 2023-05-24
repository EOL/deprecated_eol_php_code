<?php
namespace php_active_record;
/*connector for [USDA PLANTS images] http://www.eol.org/content_partners/36/resources/37
https://opendata.eol.org/dataset/usda_plants/resource/fcbdd9c9-f33f-4ec6-8c9f-218510e74d04

37	Wed 2017-11-22 09:22:30 AM	{"agent.tab":261, "media_resource.tab":22103, "reference.tab":2, "taxon.tab":67357, "vernacular_name.tab":41494}
37	Mon 2018-03-19 09:11:45 PM	{"agent.tab":261, "media_resource.tab":22103, "reference.tab":2, "taxon.tab":67357, "vernacular_name.tab":41494}
37	Wed 2023-04-05 12:57:07 AM	{"agent.tab":261, "media_resource.tab":22103, "reference.tab":2, "taxon.tab":67357, "vernacular_name.tab":41494, "time_elapsed":false}
37	Wed 2023-04-05 05:56:16 AM	{"agent.tab":261, "media_resource.tab":22103, "reference.tab":2, "taxon.tab":67357, "vernacular_name.tab":41494, "time_elapsed":false}

37.tar.gz - left the text objects, removed image objects, vernaculars and synonyms (not updated anyway)
usda_plant_images.tar.gz - (new resource) has the latest image objects, vernaculars, synonyms, taxa list. (usda_plant_images.php)

37	Mon 2023-05-22 10:57:26 PM	{"agent.tab":2, "media_resource.tab":6247, "reference.tab":2, "taxon.tab":67357, "time_elapsed":false}
37	Mon 2023-05-22 11:33:09 PM	{"agent.tab":2, "media_resource.tab":6247, "reference.tab":2, "taxon.tab":47862, "time_elapsed":false} no more synonyms
37	Tue 2023-05-23 06:25:35 AM	{"agent.tab":2, "media_resource.tab":6247, "reference.tab":2, "taxon.tab":47862, "time_elapsed":false}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 37;
$GLOBALS['ENV_DEBUG'] = true;

require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/42fd51a0-e31a-4b2a-9f18-6e4f08242d42/resource/fcbdd9c9-f33f-4ec6-8c9f-218510e74d04/download/usda-plants-images.xml.zip";
$params["filename"]     = "USDA PLANTS images 2.xml"; //extract usda-plants-images.xml.zip to get this filename
$params["dataset"]      = "USDA resource";
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 60*60*24); // false => means it is NOT an XML file, BUT an archive file OR a zip file. IMPORTANT: Expires now = 0.

$deleteYN = true; //true means delete the DwCA folder in /resources/
Functions::finalize_dwca_resource($resource_id, false, $deleteYN);
// Functions::set_resource_status_to_harvest_requested($resource_id); //obsolete
// Functions::delete_if_exists($params["eol_xml_file"]);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>