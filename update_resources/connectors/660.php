<?php
namespace php_active_record;
/* Rotifers
estimated execution time: processing text files exported from MSAccess DB: 2 minutes

latest nos.:
http://eol.org/schema/reference/reference:Total: 1462
    http://purl.org/dc/dcmitype/Text: 6101
    http://purl.org/dc/dcmitype/StillImage: 2524, 2521
    http://www.eol.org/voc/table_of_contents#TypeInformation: 4005
    http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution: 2096
http://rs.tdwg.org/dwc/terms/taxon:Total: 2101, 2755, 2724
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/RotifersAPI');

$timestart = time_elapsed();
$resource_id = 660;
$func = new RotifersAPI($resource_id);

// $func->some_stats(); exit;   //utility some stats

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
