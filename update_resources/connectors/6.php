<?php
namespace php_active_record;
/* connector for ARKive
estimated execution time:
There is already a published data for this resource. This connector modifies the resource 6.xml in Beast.
Partner is un-responsive for a while so we decided to just adjust the resource XML ourselves.
This connector will just change all text objects with subject #Description to #TaxonBiology.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 6;

/* change subject mapping from #Description to #TaxonBiology */
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = Functions::get_remote_file($resource_path);
$xml = $func->replace_data_object_element_value_with_condition( "subject", 
                                                                "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription", 
                                                                "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", 
                                                                $xml, "dc:title", "Description");
$func->save_resource_document($xml);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
exit("\n\n Done processing.");
?>