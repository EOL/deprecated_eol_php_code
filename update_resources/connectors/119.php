<?php
/* connector for Photosynth -- http://photosynth.net/
estimated execution time:
Connector sends a post request to their unofficial service and captures the result.
It also scrapes the site to get additional data (tags) which are not exposed in the API.
*/

$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('PhotosynthAPI');
$GLOBALS['ENV_DEBUG'] = false;

$wrap = "\n";
//$wrap = "<br>";

$schema_taxa = PhotosynthAPI::harvest_photosynth();
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . "119.xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml); fclose($OUT);

$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";
exit("$wrap$wrap Done processing.");

?>