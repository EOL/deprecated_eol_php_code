<?php
namespace php_active_record;
/* 
http://www.eol.org/content_partners/5/resources/19 - https://eol-jira.bibalex.org/browse/DATA-1735
This is a generic script that will convert EOL XML to EOL DWC-A
*/

echo "\nUsed [/connectors/collections_generic.php] instead\n"; //since the media_url from XML is no longer available. And media will be taken from EOL server.
// https://eol-jira.bibalex.org/browse/DATA-1735?focusedCommentId=62086&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62086
return;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 19;
$params["eol_xml_file"] = "";
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/4a668cee-f1da-4e95-9ed1-cb755a9aca4f/resource/638a9395-6beb-42de-9bbc-941b60c6bb92/download/microscope.xml";
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, false, $timestart);
?>