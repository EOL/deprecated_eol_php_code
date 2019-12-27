<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-703?focusedCommentId=63349&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63349
This is a generic script that will convert EOL XML to EOL DWC-A

20	Thursday 2019-12-26 10:52:17 AM	{"agent.tab":2031,"media_resource.tab":28979,"reference.tab":1420,"taxon.tab":8830,"time_elapsed":{"sec":32.97,"min":0.55,"hr":0.01}}
327	Thursday 2019-12-26 11:20:32 AM	{"agent.tab":120,"media_resource.tab":31927,"reference.tab":5025,"taxon.tab":17539,"vernacular_name.tab":4678,"time_elapsed":{"sec":29.3,"min":0.49,"hr":0.01}}
TaiEOL	Thursday 2019-12-26 09:57:10 PM	{"agent.tab":15,"media_resource.tab":3823,"taxon.tab":1292,"vernacular_name.tab":807,"time_elapsed":{"sec":4.74,"min":0.08,"hr":0}}
802	Thursday 2019-12-26 10:15:40 PM	{"agent.tab":2,"media_resource.tab":1394,"taxon.tab":149,"vernacular_name.tab":49,"time_elapsed":{"sec":3.34,"min":0.06,"hr":0}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']      = @$argv[2]; //useful here
$cmdline_params['expire_seconds']   = @$argv[3]; //useful here


// http://eol.taibif.tw/data_objects/66390
// https://data.taieol.tw/files/eoldata/imagecache/data_object_image/images/39/calophya_mangiferae-2-001-007-g-2.jpg
// http://nchuentm.biodiv.tw/files/nchu/imagecache/w740/images/2/Calophya%20mangiferae-2-001-007-G-2.jpg
// http://nchuentm.biodiv.tw/files/nchu/imagecache/w740/images/2/calophya_mangiferae-2-001-007-g-2.jpg

if($val = @$cmdline_params['resource_id']) $resource_id = $val;
else exit("\nNo resource_id passed. Will terminate.\n");

// $resource_id = 20; //debug
$xml[20]['url'] = 'https://opendata.eol.org/dataset/e09787e8-1428-401a-a10d-c28872f2dc93/resource/f2c6d809-abd9-4b98-9b00-39546dcb4eac/download/20.xml.zip'; //ZooKeys
$xml[327]['url'] = 'https://opendata.eol.org/dataset/1220f735-a568-47e2-adee-f1bbf65c4ffe/resource/fd17f8dd-74f7-43eb-a547-b3f65deec976/download/327.xml.zip'; //Flora of Zimbabwe
$xml['TaiEOL']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_44903.xml'; //Taiwan Encyclopedia of Life (TaiEOL)
$xml['547']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_44902.xml'; //Fish Database of Taiwan
$xml['889']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_69268.xml'; //TaiEOL Insecta

$xml[20]['xmlYN'] = false;
$xml[327]['xmlYN'] = false;
$xml['TaiEOL']['xmlYN'] = true;
$xml['547']['xmlYN'] = true;
$xml['889']['xmlYN'] = true;

$xml[20]['expire_seconds'] = false; //no expire
$xml[327]['expire_seconds'] = false;
$xml['TaiEOL']['expire_seconds'] = 60*60*24*30; //expires in a month
$xml['547']['expire_seconds'] = false;
$xml['889']['expire_seconds'] = false;

if($val = @$cmdline_params['expire_seconds']) $xml[$resource_id]['expire_seconds'] = $val;

if(!$xml[$resource_id]) exit("\nResource ID [$resource_id] not yet initialized.\n");

// $params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://...");
$params["eol_xml_file"] = $xml[$resource_id]['url'];
$params["filename"]     = $resource_id.".xml";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, $xml[$resource_id]['xmlYN'], $xml[$resource_id]['expire_seconds']); // 2nd param true => means it is an XML file, not an archive file nor a zip file. Third param false, NO expire.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>