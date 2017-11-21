<?php
namespace php_active_record;
/* connector for EMBLreptiles
SPG bought their CD. BIG exports their data from the CD to a spreadsheet.
This connector processes the spreadsheet.
Estimated execution time: 6 minutes

Steps:
1. after running 306.php, 
2. open 306.xml using FireFox. You will see which characters to delete
3. open 306.xml using TextMate then delete those bad chars.
4. repeate #2 and #3 until you have a valid XML. *No need to run XML validator for 306.xml.
5. then run 306_dwca.php to convert XML to DwCA. 306.tar.gz validates OK
*/

echo("\nJust a one-time resource, already generated. Program will terminate.\n\n"); return;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/EMBLreptiles');
$resource_id = 306; 

$func = new EMBLreptiles();
$taxa = $func->get_all_taxa($resource_id);

$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w"))) return;
fwrite($OUT, $xml);
fclose($OUT);

/* no longer needed:
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 25000) {
    Functions::set_resource_status_to_harvest_requested($resource_id);
}
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "\n\n Done processing.";
?>