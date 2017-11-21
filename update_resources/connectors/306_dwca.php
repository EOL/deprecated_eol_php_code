<?php
namespace php_active_record;
/* connector for EMBLreptiles
run 306.php before running this connector (306_dwca.php).
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/EMBLreptiles');
$resource_id = 306; 

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 25000) {
    require_library('ResourceDataObjectElementsSetting');
    $nmnh = new ResourceDataObjectElementsSetting($resource_id);
    $nmnh->call_xml_2_dwca($resource_id, "EMBLreptiles", false); //3rd param false means this resource is not an NMNH resource
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "\n\n Done processing.";
?>