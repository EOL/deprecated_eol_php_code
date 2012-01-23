<?php
namespace php_active_record;
/* connector for MarLIN - Marine Life Information Network
Partner provides the EOL XML.
This connector just loads the partner resource and removes erroneous string(s).
estimated execution time: 20 seconds
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 195;
$file = "http://www.marlin.ac.uk/downloads/EOL/EOL.xml";
//$contents = Functions::get_remote_file($file); this one, operation times out
$contents = file_get_contents($file);
$contents = str_ireplace("No text entered", "", $contents);  
$contents = str_ireplace("<![CDATA[", "", $contents);  
$contents = str_ireplace("]]>", "", $contents);  
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w");
fwrite($OUT, $contents);
fclose($OUT);
// set to force harvest
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"))
{
    $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\nDone processing.");
?>