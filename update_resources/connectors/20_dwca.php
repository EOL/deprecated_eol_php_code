<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-703?focusedCommentId=63349&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63349
This is a generic script that will convert EOL XML to EOL DWC-A
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 20;
// $params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://...");
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/e09787e8-1428-401a-a10d-c28872f2dc93/resource/f2c6d809-abd9-4b98-9b00-39546dcb4eac/download/20.xml.zip";
$params["filename"]     = "20.xml";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 60*60*24*5); // true => means it is an XML file, not an archive file nor a zip file. Expires in 5 days.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>