<?php
/* connector for Afrotropical 
estimated execution time: 
This connector reads an EOL XML and converts PDF files stored in <mediaURL> into text description objects.
*/
exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AfrotropicalAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = AfrotropicalAPI::get_all_taxa();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "138.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

echo "time: ". Functions::time_elapsed()."\n";
?>
