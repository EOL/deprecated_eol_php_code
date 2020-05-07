<?php
namespace php_active_record;
/* connector for YouTube 
estimated execution time: 1 minute. But this will change as the number of EOL YouTube subscriptions increase.

2015-05-30
taxon 		= 497
commonName 	= 16
videos 		= 497
*/

// setting a 2 second wait time because we were getting yt:quota, too_many_recent_calls errors
define('DOWNLOAD_WAIT_TIME', 2000000);
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* works OK generates the EOL XML (.gz)
require_library('connectors/YouTubeAPI');
$resource_id = 323;
if(!Functions::can_this_connector_run($resource_id)) return;

$func = new YouTubeAPI();
$taxa = $func->get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w"))) return;
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_harvest_requested($resource_id);
Functions::gzip_resource_xml($resource_id);
// */

//=================================================== XML generation ends here...

/* This supposedly should convert the XML to DwCA. But not successfull. Won't fix since YouTube is no longer used in eol.org.
require_library('connectors/ConvertEOLtoDWCaAPI');
$resource_id = 323;
$params["eol_xml_file"] = "";
$params["eol_xml_file"] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/323.xml.gz";
$params["filename"]     = "323.xml"; //no need to mention if eol_xml_file is already .xml and not .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, false, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
*/
?>