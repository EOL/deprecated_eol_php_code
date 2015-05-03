<?php
namespace php_active_record;
/* Tropical Lichens connector
estimated execution time: 8 seconds
Partner provides a service that resembles an EOL XML.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 69;
$url = 'http://www.tropicallichens.net/eolclient.aspx';
if($xml_content = Functions::get_remote_file($url)) 
{
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    if(!($OUT = fopen($resource_path, "w")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    fwrite($OUT, $xml_content);
    fclose($OUT);
}
else print "\n no contents $i";

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours  \n";
echo "\n\n Done processing.";
?>