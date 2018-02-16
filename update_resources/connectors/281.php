<?php
namespace php_active_record;
/* connector for Natural History Museum Species of the day
estimated execution time: 1 second
There is already a published data for this resource. This connector modifies the resource 281.xml in Beast.
This resource is set to import once.
This connector will replace all <subject> elements to "#TaxonBiology" only if <dc:title> is "Introduction".
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 281;

echo "\nUsed lifedesk_combine.php instead.\n";
return;

require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";

//if partner will provide an accesspointURL the connector will use that, if not it will use what is uploaded.
$result = $GLOBALS['db_connection']->select("SELECT accesspoint_url FROM resources WHERE id=$resource_id");
$row = $result->fetch_row();
$new_resource_path = $row[0];
if($new_resource_path != '') $resource_path = $new_resource_path;
print "\n processing resource:\n $resource_path \n\n"; 

$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = Functions::get_remote_file($resource_path);
$xml = $func->replace_data_object_element_value_with_condition("subject", "", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", $xml, "dc:title", "Introduction", false);
$func->save_resource_document($xml);

Functions::set_resource_status_to_harvest_requested($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "\n\n Done processing.";
?>