<?php
namespace php_active_record;
/* http://eol.org/content_partners/280/resources/191 (PhytoKeys for EOL) - DATA-1622
This is a generic script that will convert EOL XML to EOL DWC-A
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 191;
$params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://www.pensoft.net/J_FILES/EoLData/PhytoKeys.xml");
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "Pensoft XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true); // true => means it is an XML file, not an archive file nor a zip file
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
?>