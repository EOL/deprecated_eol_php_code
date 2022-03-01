<?php
namespace php_active_record;
/* DATA-1903: iNaturalist image connector from GBIF export
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/iNatImagesAPI');
$timestart = time_elapsed();
$resource_id = 'inat_images';
$func = new iNatImagesAPI($resource_id, false, true);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
?>