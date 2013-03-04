<?php
namespace php_active_record;
/* connector for WORMS
Partner provides a service to get their list of IDs and another service to use the id to get each taxon information.
estimated execution time: 15 days
sample API: http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=571925

Connector can still run but takes at least two weeks to process about 201k taxa. For the meantime the connector is disabled.
Soon this connector will be obsolete as WORMS agreed to provide a DWC-A resource. No timetable yet though.
*/

// include_once(dirname(__FILE__) . "/../../config/environment.php");
// 
// define('DOWNLOAD_WAIT_TIME', '200000');
// include_once(dirname(__FILE__) . "/../../config/environment.php");
// $timestart = time_elapsed();
// require_library('connectors/WormsAPI');
// $resource_id = 26;
// 
// $worms = new WormsAPI();
// $worms->initialize_text_files();
// // Functions::kill_running_connectors($resource_id);
// $worms->start_process($resource_id, false);
// 
// $elapsed_time_sec = time_elapsed() - $timestart;
// echo "\n";
// echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
// echo "elapsed time = " . $elapsed_time_sec/60/60/24 . " days \n";
// echo date('Y-m-d h:i:s a', time())."\n";
// echo "\n\n Done processing.";


function worms_check()
{
    $path = "http://localhost/~eolit/eli/eol_php_code/applications/content_server/resources/26.xml";
    print "\n xml file: [$path] \n";
    $reader = new \XMLReader();
    $reader->open($path);
    $i = 0;
    while(@$reader->read())
    {
        if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
        {
            $string = $reader->readOuterXML();
            if($xml = simplexml_load_string($string)) print " ok ";
            else 
            {
                print " bad "; $i++;
            }
        }
    }
    print "\n\n invalid xml: $i";
}

?>
