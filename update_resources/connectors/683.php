<?php
namespace php_active_record;
/* Diptera of Central America
estimated execution time: 9 minutes 
Connector scrapes the partner's website
683	Thursday 2018-08-02 09:09:58 AM	{"agent.tab":1,                                        "media_resource.tab":200,                               "taxon.tab":171}  eol-archive
below starts resource with trait data:
683	Thursday 2019-02-14 11:02:24 AM	{"agent.tab":1,"measurement_or_fact_specific.tab":8970,"media_resource.tab":200,"occurrence_specific.tab":8970,"taxon.tab":4585} eol-archive
683	Wednesday 2019-02-20 01:25:28 AM{"agent.tab":1,"measurement_or_fact_specific.tab":8970,"media_resource.tab":200,"occurrence_specific.tab":8970,"taxon.tab":4585}
683	Thursday 2019-02-21 03:24:47 AM	{"agent.tab":1,"measurement_or_fact_specific.tab":9028,"media_resource.tab":200,"occurrence_specific.tab":9028,"taxon.tab":4585} with additional mappings from Jen
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DipteraCentralAmericaAPI');
$timestart = time_elapsed();
$resource_id = "683"; // formerly 661
$func = new DipteraCentralAmericaAPI($resource_id);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete working resource folder
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>