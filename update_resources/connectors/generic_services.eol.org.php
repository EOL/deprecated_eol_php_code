<?php
namespace php_active_record;
/* connector for: converting XML from services.eol.org to DwCA.
(0028) Alaska Fisheries Science Center
(0356) Protoanguilla palau video

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

/* $ generic_services.eol.org.php jenkins 28 */
$cmdline_params['jenkins_or_cron']      = @$argv[1]; //irrelevant here
$cmdline_params['resource_id_2process'] = @$argv[2]; //useful here
$resource_id = false;
if($val = @$cmdline_params['resource_id_2process']) $resource_id = $val;
if($resource_id) echo "\n with resource_id";
else {
    echo "\n Without resource_id. Will terminate.\n";
    return;
}

//start converting to DwCA
require_library('connectors/ConvertEOLtoDWCaAPI');
$params["eol_xml_file"] = "http://services.eol.org/resources/".$resource_id.".xml";
$params["filename"]     = "no need to mention here.xml"; //no need to mention if eol_xml_file is already .xml and not .xml.gz
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete the dwca folder.
//end conversion

$elapsed_time_sec = time_elapsed()-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>