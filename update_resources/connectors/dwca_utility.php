<?php
namespace php_active_record;
/*  
Processes a DwCA file, preferably an EOL DwCA file.
For non-EOL DwCA file, the result archive will only consist of extensions and fields that are understood by the EOL DwCA.
*Another similar library is DWCA_Utility_cmd.php. This one will process a DwCA taxa extension (taxon.tab/txt/tsv). And this one is run as command-line in terminal.
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DwCA_Utility');
$timestart = time_elapsed();
ini_set("memory_limit","9000M"); // trying for the dwh_try3.zip, didn't work yet

// $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ioc-birdlist.tar.gz";
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ICTV-virus_taxonomy.tar.gz";
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/26.tar.gz";
// $dwca_file = "http://localhost/cp/dynamic_hierarchy/amphibia.zip";
// $dwca_file = "http://localhost/cp/dynamic_hierarchy/dwca-phasmida-v10.6.zip";
$dwca_file = "http://localhost/cp/dynamic_hierarchy/dwh_try3.zip";

$resource_id = get_base_filename($dwca_file);

// /* //main operation
$func = new DwCA_Utility($resource_id, $dwca_file);
$func->convert_archive_by_adding_higherClassification();
Functions::finalize_dwca_resource($resource_id);
unset($func);
// */

// /* //utility
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/dwca-phasmida-v10-with-higherClassification.tar.gz"; //debug -> if you want to supply a diff. dwca
$func = new DwCA_Utility(NULL, $dwca_file);
$func->count_records_in_dwca();
unset($func);
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function get_base_filename($dwca_file)
{
    $info = pathinfo($dwca_file);
    $arr = explode(".", $info['filename']);
    return $arr[0]."-with-higherClassification";
}
?>
