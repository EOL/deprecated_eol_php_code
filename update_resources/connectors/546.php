<?php
namespace php_active_record;
/* This is BOLDS 2nd image resource, gets the data from BOLDS big XML file but excluding those images from the original image resource.
estimated execution time: 7.5 hours

as of 5-Aug-2014
http://rs.tdwg.org/dwc/terms/taxon: Total: 99728
http://eol.org/schema/agent/agent:  Total: 119
http://purl.org/dc/dcmitype/StillImage: 90982

546	Monday 2018-03-19 12:14:22 AM	{"agent.tab":119,"media_resource.tab":90982,"taxon.tab":99728} - MacMini

546.php works OK locally. Will run in Archive once there is enough harddisk space.
*/
return;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BoldsImagesAPIv2');

$timestart = time_elapsed();
$resource_id = 546;
$func = new BoldsImagesAPIv2($resource_id);
$func->get_all_taxa();

Functions::finalize_dwca_resource($resource_id, false, true);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>