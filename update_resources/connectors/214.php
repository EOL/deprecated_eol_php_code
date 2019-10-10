<?php
namespace php_active_record;
/* connector for Vimeo 
estimated execution time: 46 minutes

                    Jul20
agent.tab           [20]
media_resource.tab  [254]
taxon.tab           [172]
vernacular_name.tab [38]
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/VimeoAPI');

$resource_id = 214;
if(!Functions::can_this_connector_run($resource_id)) return;
$func = new VimeoAPI();
$taxa = $func->get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w"))) return;
fwrite($OUT, $xml);
fclose($OUT);

//start converting to DWC-A file
echo "\n\nStart converting to DWC-A file...";
require_library('connectors/ConvertEOLtoDWCaAPI');

$params["eol_xml_file"] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "Vimeo XML file";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true); // true => means it is an XML file, not an archive file nor a zip file
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);

unlink($params["eol_xml_file"]);
?>