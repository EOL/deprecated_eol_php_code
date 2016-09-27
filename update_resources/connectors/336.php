<?php
namespace php_active_record;
/* connector for Royal Botanic Garden Edinburgh: Herbarium Specimen Images (E)
estimated execution time: 
There is already a published data for this resource that is set to 'import once'. 
The connector modifies the 336.xml in Beast.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

$resource_id = 336;

//--------------
/* set rating to 2 */
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $func->set_data_object_rating_on_xml_document();
$func->save_resource_document($xml);
//--------------

Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>