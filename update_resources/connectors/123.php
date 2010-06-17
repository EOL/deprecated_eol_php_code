<?php
exit;
/* connector for AquaMaps
estimated execution time: 

This connector reads an XML (list of species with AquaMaps) then loops on each species 
and access the external service (provide by AquaMaps) to get the distribution maps.

Maps are shown in the Distribution section.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AquaMapsAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = AquamapsAPI::get_all_eol_photos();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "123.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

echo "time: ". Functions::time_elapsed()."\n";
?>