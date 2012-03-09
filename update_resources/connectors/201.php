<?php
namespace php_active_record;

/* connector for MCZ Harvard
estimated execution time: 15 mins.
Partner provides a CSV file.      
    - download the CSV from remote 
    - add the label headers
    - then start processing...

Reminders: 
- In CSV, there are some entries that starts with '=' (the equal sign) - can be mistaken to be an Excel formula.
- Connector can be improved by reading the CSV using fopen() and don't use PHPExcel library.
*/

$timestart = microtime(1);
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MCZHarvardAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = MCZHarvardAPI::get_all_taxa();
$xml = \SchemaDocument::get_taxon_xml($taxa);
$resource_id = 201;
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

Functions::set_resource_status_to_force_harvest($resource_id);

$elapsed_time_sec = microtime(1)-$timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
exit("\n\n Done processing.");
?>