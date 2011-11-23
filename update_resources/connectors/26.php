<?php
namespace php_active_record;
/* connector for WORMS
Partner provides a service to get their list of IDs and another service to use the id to get each taxon information.
estimated execution time:
sample API: http://www.marinespecies.org/aphia.php?p=eol&action=taxdetails&id=571925
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/WormsAPI');
$resource_id = 26;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

WormsAPI::start_process($resource_id, $call_multiple_instance, false);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo date('Y-m-d h:i:s a', time())."\n";

if($elapsed_time_sec < 1) 
{
    print "\n\n";
    print "=======================================================================\n";
    print "Previous abnormal interruption detected. Files initialized. Retrying...\n";
    print "=======================================================================\n";
    WormsAPI::start_process($resource_id, $call_multiple_instance, true);
}

exit("\n\n Done processing.");
?>