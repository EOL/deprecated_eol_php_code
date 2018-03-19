<?php
namespace php_active_record;
/* connector for BOLD Systems -- species-level taxa
estimated execution time 1.5 | 7.2 hours
Partner provides XML service and a big XML file.
No need to run multiple connectors anymore since we got the big XML file.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

/* used generic_services.eol.org for now. So just to bring it to V3. Might need to update connector to get latest data.
212	Sunday 2018-03-18 12:02:18 PM	{"agent.tab":2,"media_resource.tab":449425,"taxon.tab":241347}
*/
return;




$resource_id = 212;

/* utility Functions::count_rows_from_text_file(DOC_ROOT . "temp/media_resource.tab"); */

$folder = DOC_ROOT . "update_resources/connectors/files/BOLD";
if(!file_exists($folder)) mkdir($folder , 0777);

require_library('connectors/BOLDSysAPI');
$bolds = new BOLDSysAPI();

// /* This will store DNA sequence on a json file, un-comment if u want this re-created everytime you run 212.php. Will last around 57 mins. excluding the time downloading the big dump file from BOLDS
$bolds->save_dna_sequence_from_big_xml(); 
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done save_dna_sequence_from_big_xml() \n\n";
// */

$bolds->initialize_text_files(); // not commented on regular operation. If running multiple connectors, the first connector will pass here and succeeding ones won't.

require_library('connectors/BOLDSysArchiveAPI');
$func = new BOLDSysArchiveAPI($resource_id);
$func->start_process($resource_id, false);

Functions::finalize_dwca_resource($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing. \n";

?>