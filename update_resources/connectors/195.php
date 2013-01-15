<?php
namespace php_active_record;
/* connector for MarLIN - Marine Life Information Network
Partner provides the EOL XML.
This connector just loads the partner resource and removes erroneous string(s).
estimated execution time: 81 seconds
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 195;
$file = "http://www.marlin.ac.uk/downloads/EOL/EOL.xml";
if(!$contents = Functions::get_remote_file($file, DOWNLOAD_WAIT_TIME, 999999))
{
    echo "\n\n Content partner's server is down, connector will now terminate.\n";
}elseif(stripos($contents, "The page you are looking for has been moved.") != "")
{
    echo "\n\n Content partner's server is down, connector will now terminate.\n";
}else
{
    $contents = str_ireplace("No text entered", "", $contents);  
    $contents = str_ireplace("<![CDATA[", "", $contents);  
    $contents = str_ireplace("]]>", "", $contents);  
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    $OUT = fopen($resource_path, "w");
    fwrite($OUT, $contents);
    fclose($OUT);
    Functions::set_resource_status_to_force_harvest($resource_id);
    $elapsed_time_sec = time_elapsed() - $timestart;
    echo "\n";
    echo "elapsed time = $elapsed_time_sec sec              \n";
    echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
    echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
    echo "\n\n Done processing.";
}
?>