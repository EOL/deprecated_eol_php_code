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
exit("\nThis has been run already.
\n6.xml is stored in: https://opendata.eol.org/dataset/arkive/resource/e03c421b-6d75-4586-97cd-b607907bbe65
\nxml_2_dwca.php is now used to generate 6.tar.gz\n\n");
/* change subject mapping from #Description to #TaxonBiology */
require_library('ResourceDataObjectElementsSetting');
$resource_path = "http://dl.dropbox.com/u/7597512/resources/6.xml.gz";

$result = $GLOBALS['db_connection']->select("SELECT accesspoint_url FROM resources WHERE id=$resource_id");
$row = $result->fetch_row();
$new_resource_path = $row[0];
if($resource_path != $new_resource_path && $new_resource_path != '') $resource_path = $new_resource_path;
print "\n processing resource:\n $resource_path \n\n"; 

$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path);
$xml = $func->load_xml_string();
$xml = $func->replace_data_object_element_value_with_condition( "subject", 
                                                                "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription", 
                                                                "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", 
                                                                $xml, "dc:title", "Description");
$func->save_resource_document($xml);

Functions::set_resource_status_to_harvest_requested($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "\n\n Done processing.";
?>