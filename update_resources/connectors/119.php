<?php
/* connector for Photosynth
estimated execution time: 

This connector will use an un-official service to search the Photosynth server.
It also scrapes the Photosynth site to get the tags entered by owners as the tags where not included in the service.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/PhotosynthAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = PhotosynthAPI::get_all_eol_photos();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "119.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

echo "time: ". Functions::time_elapsed()."\n";

?>
