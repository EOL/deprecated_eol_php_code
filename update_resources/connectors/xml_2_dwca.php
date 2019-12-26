<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-703?focusedCommentId=63349&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63349
This is a generic script that will convert EOL XML to EOL DWC-A

20	Thursday 2019-12-26 10:52:17 AM	{"agent.tab":2031,"media_resource.tab":28979,"reference.tab":1420,"taxon.tab":8830,"time_elapsed":{"sec":32.97,"min":0.55,"hr":0.01}}
327	Thursday 2019-12-26 11:20:32 AM	{"agent.tab":120,"media_resource.tab":31927,"reference.tab":5025,"taxon.tab":17539,"vernacular_name.tab":4678,"time_elapsed":{"sec":29.3,"min":0.49,"hr":0.01}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']      = @$argv[2]; //useful here

if($val = @$cmdline_params['resource_id']) $resource_id = $val;
else exit("\nNo resource_id passed. Will terminate.\n");

// $resource_id = 20; //debug
$xml[20] = 'https://opendata.eol.org/dataset/e09787e8-1428-401a-a10d-c28872f2dc93/resource/f2c6d809-abd9-4b98-9b00-39546dcb4eac/download/20.xml.zip';
$xml[327] = 'https://opendata.eol.org/dataset/1220f735-a568-47e2-adee-f1bbf65c4ffe/resource/fd17f8dd-74f7-43eb-a547-b3f65deec976/download/327.xml.zip';
if(!$xml[$resource_id]) exit("\nResource ID [$resource_id] not yet initialized.\n");

// $params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://...");
$params["eol_xml_file"] = $xml[$resource_id];
$params["filename"]     = $resource_id.".xml";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, false); // 2nd param true => means it is an XML file, not an archive file nor a zip file. Third param, NO expire.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>