<?php
namespace php_active_record;
/* DATA-1792 
                                        {"measurement_or_fact.tab":5574,                         "occurrence.tab":1225,                  "taxon.tab":657}
atdb	Thursday 2019-01-17 03:00:02 AM	{"measurement_or_fact.tab":5574,         "media_resource.tab":659,"occurrence.tab"         :1225,"reference.tab":1,"taxon.tab":660,"vernacular_name.tab":995}
atdb	Tuesday 2019-01-29 08:46:57 AM	{"measurement_or_fact_specific.tab":5574,"media_resource.tab":659,"occurrence_specific.tab":1225,"reference.tab":1,"taxon.tab":661,"vernacular_name.tab":995} MacMini
atdb	Tuesday 2019-01-29 09:23:59 AM	{"measurement_or_fact_specific.tab":5574,"media_resource.tab":659,"occurrence_specific.tab":1225,"reference.tab":1,"taxon.tab":661,"vernacular_name.tab":995} Archive

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AfricaTreeDBAPI');
$timestart = time_elapsed();

/* just a test
$arr = Functions::get_eol_defined_uris();
print_r($arr); exit("\n-end-\n");
*/

$dwca_file = "https://opendata.eol.org/dataset/e31baa95-af6c-4539-a1d8-00f7364fadcd/resource/c39822fe-79a1-4fd9-929f-0ca6e495f3b4/download/archive.zip";
$resource_id = "atdb"; //africa tree db
$func = new AfricaTreeDBAPI($resource_id, $dwca_file);

/* utilities - to get the un-mapped strings
$func->get_unmapped_strings();
exit("\n-end utility-\n");
*/

$func->convert_archive(); //main operation
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
