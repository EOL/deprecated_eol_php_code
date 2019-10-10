<?php
namespace php_active_record;
/* connector for SERPENT
estimated execution time: 23 mins. | 1.5 hours (total taxa has increased and I increased download wait time to 1 sec.)
Connector screen scrapes the partner website.

170	Monday 2018-04-16 11:34:23 PM	{"media_resource.tab":1880,"taxon.tab":513} eol-archive
170	Monday 2019-09-23 10:26:56 AM	{"media_resource.tab":1892,"taxon.tab":514} eol-archive

IMPORTANT: after this connector, run 170_final.php to convert .mov to .mp4.

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

require_library('connectors/SerpentAPI');
$func = new SerpentAPI();
$taxa = $func->get_all_taxa();
/* $taxa = SerpentAPI::get_all_taxa();  --- old code */
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_id = 170;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w+"))) return;
fwrite($OUT, $xml);
fclose($OUT);

//start convert XML to DwCA ---------------------------------------
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = $resource_path;
$params["filename"]     = "170.xml"; //need to mention 40.xml here because eol_xml_file is .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>