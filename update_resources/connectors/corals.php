<?php
namespace php_active_record;
/* Corals of the World - text objects and structured data
estimated execution time:
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/CoralsOfTheWorldAPI');
$timestart = time_elapsed();
$resource_id = 1;

/*                                          27Apr
    http://rs.tdwg.org/dwc/terms/taxon:     794
    http://eol.org/schema/agent/agent:      62
    http://purl.org/dc/dcmitype/StillImage: 6553
    http://purl.org/dc/dcmitype/Text:       3776

    http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology:        792
    http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat:           794
    http://rs.tdwg.org/ontology/voc/SPMInfoItems#PopulationBiology: 794
    http://rs.tdwg.org/ontology/voc/SPMInfoItems#LookAlikes:        602
    http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology:      794
*/
$func = new CoralsOfTheWorldAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>