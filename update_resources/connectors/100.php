<?php
namespace php_active_record;
/*connector for CONABIO
estimated execution time: 11 minutes for 500 taxa
Partner provides a list of URL's for its individual species XML.
The connector loops to this list and compiles each XML to 1 final XML for EOL ingestion.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

/* just test
// $WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "eli.txt", "a");
// fwrite($WRITE, date('l jS \of F Y h:i:s A') . "\n");
// fclose($WRITE);

$url = "http://parser.globalnames.org/api?q=";
$str = "Fratiidae Ho, Conradi & López-González, 1998";
$str = urlencode($str);

// $str = "Palicus Philippi & Eli, 1838";
// $str = urlencode($str);
echo "\n[$str]\n";
$json = Functions::lookup_with_cache($url.$str);
print_r(json_decode($json, true));


exit("\n-ends here\n");
*/

require_library('connectors/ConabioAPI');
$resource_id = 100;
$func = new ConabioAPI();
$func->combine_all_xmls($resource_id);

if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 1000)
{
    // Functions::set_resource_status_to_harvest_requested($resource_id);
    // Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate XML file
    
    require_library('ResourceDataObjectElementsSetting');
    $nmnh = new ResourceDataObjectElementsSetting($resource_id);
    $nmnh->call_xml_2_dwca($resource_id, "CONABIO", false); //3rd param false means this resource is not an NMNH resource
}

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
?>