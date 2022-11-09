<?php
namespace php_active_record;
/* ADW - comprehensive resource
agent               : 3847
media_resource      : 98813     79985
reference           : 39939
taxon               : 9134
vernacular          : 3538

Text                : 53485
StillImage          : 44244
Sound               : 1380

#TaxonBiology       : 274
#Notes              : 2072
#Distribution       : 3934
#Habitat            : 3932
#Morphology         : 3908
#Development        : 1060
#Reproduction       : 8630
#LifeExpectancy     : 2779
#Behaviour          : 6571
#TrophicStrategy    : 3940
#Associations       : 5656
#Uses               : 7057
#ConservationStatus : 3672

measurementorfact   : 185889
subtyp map          : 42
------------------------------------------------------------

agent.tab               [3830]
measurement_or_fact.tab [185890]
media_resource.tab      [75350]
occurrence.tab          [176247]
reference.tab           [39940]
taxon.tab               [9134]
vernacular_name.tab     [3539]
*/

exit("\nThis one seems obsolete already.\n");
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/AdwAPI');

/*
$final = array();
foreach($arr as $l) {
    $final[] = str_ireplace(array("\n"), " ", trim(Functions::remove_whitespace($l)));
}
print_r($final);
*/

$timestart = time_elapsed();
$resource_id = 1;
$func = new AdwAPI($resource_id);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>