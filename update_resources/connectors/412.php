<?php
namespace php_active_record;
/* DATA-1589 assist EOL China with xml resource

This is a generic script that will convert EOL XML to EOL DWC-A

http://rs.gbif.org/terms/1.0/vernacularname:
                                zh:         2667
                                en:         993
                                Total:      3660

http://rs.tdwg.org/dwc/terms/taxon:         2300
http://eol.org/schema/reference/reference:  1690
http://eol.org/schema/agent/agent:          104

http://purl.org/dc/dcmitype/Text:           5650
http://purl.org/dc/dcmitype/StillImage:     464
                                    Total:  6114
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$params["eol_xml_file"] = "http://localhost/cp/EOL_China/FaunaSinica_Aves.zip";
$params["eol_xml_file"] = "https://dl.dropboxusercontent.com/u/7597512/EOL_China/FaunaSinica_Aves.zip";
$params["filename"]     = "FaunaSinica_Aves.xml";
$params["dataset"]      = "EOL China";
$params["resource_id"]  = 412;

/* Sample way to access the generic script of converting EOL XML to EOL DWCA
$params["eol_xml_file"] = "http://localhost/eol_php_code/applications/content_server/resources/511.xml.gz";
$params["filename"]     = "511.xml";
$params["dataset"]      = "EOL XML";
$params["resource_id"]  = 1;
*/

$resource_id = $params["resource_id"];
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params);
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working/taxon.tab") > 1000)
{
    if(is_dir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id))
    {
        recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
        Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous");
    }
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working", CONTENT_RESOURCE_LOCAL_PATH . $resource_id);
    Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_working.tar.gz", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    Functions::set_resource_status_to_force_harvest($resource_id);
    Functions::count_resource_tab_files($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>