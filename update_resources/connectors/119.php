<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('PhotosynthAPI');
$GLOBALS['ENV_DEBUG'] = false;


$taxa = PhotosynthAPI::get_all_eol_photos($auth_token);
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "119.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

echo "time: ". Functions::time_elapsed()."\n";

?>
