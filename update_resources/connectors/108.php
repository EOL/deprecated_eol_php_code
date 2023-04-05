<?php
namespace php_active_record;
/*connector for [USDA PLANTS text] http://www.eol.org/content_partners/36/resources/108
https://opendata.eol.org/dataset/usda_plants/resource/aca53f69-b18a-40b9-adbc-34ff282874ea

108	Mon 2018-03-19 08:45:37 PM	{"agent.tab":7, "media_resource.tab":3503, "taxon.tab":3503}
108	Wed 2023-04-05 06:24:05 AM  {"agent.tab":7, "media_resource.tab":3503, "taxon.tab":3503, "time_elapsed":{"sec":10.78, "min":0.18, "hr":0}}

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 108;
$GLOBALS['ENV_DEBUG'] = true;

require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/42fd51a0-e31a-4b2a-9f18-6e4f08242d42/resource/aca53f69-b18a-40b9-adbc-34ff282874ea/download/usda-plants-text.xml.zip";
$params["filename"]     = "USDA PLANTS text 2.xml"; //extract usda-plants-text.xml.zip to get this filename
$params["dataset"]      = "USDA resource";
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 0); // false => means it is NOT an XML file, BUT an archive file OR a zip file. IMPORTANT: Expires now = 0.

$deleteYN = true; //true means delete the DwCA folder in /resources/
Functions::finalize_dwca_resource($resource_id, false, $deleteYN, $timestart);
// Functions::set_resource_status_to_harvest_requested($resource_id); //obsolete

// Functions::delete_if_exists($params["eol_xml_file"]);
?>