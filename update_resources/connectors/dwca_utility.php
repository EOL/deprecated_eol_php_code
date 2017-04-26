<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DwCA_Utility');
$timestart = time_elapsed();

// $dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ioc-birdlist.tar.gz";
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/ICTV-virus_taxonomy.tar.gz";
// $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/26.tar.gz";
// $dwca_file = "http://localhost/cp/dynamic_hierarchy/amphibia.zip";


$dwca_file = "http://localhost/cp/dynamic_hierarchy/dwca-phasmida-v10.6.zip";

$resource_id = get_base_filename($dwca_file);
// exit("\n[$resource_id]\nstop muna\n");

// /* //main operation
$func = new DwCA_Utility($resource_id, $dwca_file);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
// */

// /* //utility
$func = new DwCA_Utility(NULL, $dwca_file);
$func->count_records_in_dwca();
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
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
