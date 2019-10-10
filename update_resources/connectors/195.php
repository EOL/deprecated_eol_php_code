<?php
namespace php_active_record;
/* connector for MarLIN - Marine Life Information Network
Partner provides the EOL XML.
This connector just loads the partner resource and removes erroneous string(s).
estimated execution time: 2 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 195;
$file = "http://www.marlin.ac.uk/downloads/EOL/EOL.xml";
if(!$contents = Functions::get_remote_file($file, array('timeout' => 172800))) {
    echo "\n\n Content partner's server is down, connector will now terminate.\n";
}
elseif(stripos($contents, "The page you are looking for has been moved.") != "") {
    echo "\n\n Content partner's server is down, connector will now terminate.\n";
}
else {
    $contents = str_ireplace("No text entered", "", $contents);
    $contents = str_ireplace('<synonym relationship="synonym">None</synonym>', '', $contents);
    $contents = str_ireplace("<![CDATA[", "", $contents);
    $contents = str_ireplace("]]>", "", $contents);
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    if(!($OUT = fopen($resource_path, "w"))) {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    fwrite($OUT, $contents);
    fclose($OUT);

    list_all_common_names($resource_id); // a one-time function used to list all common names for SPG review.
    remove_erroneous_common_names($resource_id);
    list_all_common_names($resource_id);

    Functions::set_resource_status_to_harvest_requested($resource_id);
    $elapsed_time_sec = time_elapsed() - $timestart;
    echo "\n";
    echo "elapsed time = $elapsed_time_sec sec              \n";
    echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
    echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
    echo "\n\n Done processing.";
}
function remove_erroneous_common_names($resource_id)
{
    $file = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
    $xml = simplexml_load_file($file);
    foreach($xml->taxon as $taxon) {
        $dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        echo "\n " . "sciname: [" . $dwc->ScientificName."]";
        $i = 0;
        foreach($taxon->commonName as $name) {
            if(preg_match("/^A (.*?)/ims", $name, $match) || preg_match("/^An (.*?)/ims", $name, $match)) {
                echo "\n deleted common name: [$name]\n";
                $taxon->commonName[$i] = "";
            }
            $i++;
        }
    }
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    if(!($OUT = fopen($resource_path, "w"))) {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
      return;
    }
    fwrite($OUT, $xml->asXML());
    fclose($OUT);
    return $xml->asXML();
}
Function list_all_common_names($resource_id)
{
    $file = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
    $xml = simplexml_load_file($file);
    foreach($xml->taxon as $t) {
        $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        echo "\n $t_dwc->ScientificName -- ";
        foreach($t->commonName as $name) echo "[$name] ";
    }
    echo "\n\n";
}
?>