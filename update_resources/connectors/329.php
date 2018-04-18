<?php
namespace php_active_record;
/* connector for BOLDS images
estimated execution time:  3 minutes
Provider provides a big text file.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BoldsImagesAPI');
$timestart = time_elapsed();


/* used generic_services.eol.org for now. So just to bring it to V3. Might need to update connector to get latest data.
329	Sunday 2018-03-18 09:31:35 PM	{"agent.tab":55,"media_resource.tab":90564,"taxon.tab":9190}
----------------------------[stats]----------------------------
http://eol.org/schema/agent/agent
--- Total = 55
http://eol.org/schema/media/document
Total by type
------ http://purl.org/dc/dcmitype/StillImage = 90564
Total by license
------ http://creativecommons.org/licenses/by-nc-sa/3.0/ = 85950
------ http://creativecommons.org/licenses/by/3.0/ = 3652
------ http://creativecommons.org/licenses/by-nc/3.0/ = 867
------ http://creativecommons.org/licenses/by-sa/3.0/ = 95
Total by language
------ en = 90564
Total by format
------ image/jpeg = 90564
--- Total = 90564
http://rs.tdwg.org/dwc/terms/taxon
--- Total = 9190
--------------------------------------------------------------- Back to main
*/
return;


$resource_id = 329;
BoldsImagesAPI::get_all_taxa($resource_id);

//--------------
/* set rating to 2 */
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$bolds = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $bolds->set_data_object_rating_on_xml_document();
$bolds->save_resource_document($xml);
//--------------

Functions::set_resource_status_to_harvest_requested($resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>