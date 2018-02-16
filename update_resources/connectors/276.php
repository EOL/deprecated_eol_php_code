<?php
namespace php_active_record;
/* connector for INBio
Partner provides DWC-A file
estimated execution time: 18 minutes
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/INBioAPI');
$resource_id = 276;


echo "\nThe media from their DwCA is now offline. Will use collections_generic.php instead.\n";
return;

// $dwca_file = "http://localhost/~eolit/dwca-inbio-eol.zip"; //zip extracts directly to temp_dir
// $dwca_file = "http://localhost/~eolit/dwca_inbio.zip"; //zip extracts it within a folder inside temp_dir
// $dwca_file = "http://localhost/~eolit/dwca_inbio_small.zip";
// $dwca_file = "http://localhost/~eolit/dwca.tar.gz";
$dwca_file = "http://dl.dropbox.com/u/7597512/INBIO/dwca_inbio.zip";
$dwca_file = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/INBIO/dwca_inbio.zip";
$func = new INBioAPI();
if($taxa = $func->get_all_taxa($dwca_file))
{
    $xml = \SchemaDocument::get_taxon_xml($taxa);
    $xml = $func->assign_eol_subjects($xml);
    $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
    if(!($OUT = Functions::file_open($resource_path, "w"))) return;
    fwrite($OUT, $xml);
    fclose($OUT);
    Functions::gzip_resource_xml($resource_id);
    Functions::set_resource_status_to_harvest_requested($resource_id);
}
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\nelapsed time = " . $elapsed_time_sec . " seconds";
echo "\nelapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\nDone processing.";
?>