<?php
namespace php_active_record;
/* DATA-1871: new NMNH image connector from GBIF export
nmnh_images	Thu 2020-12-24 05:28:07 AM	{"agent.tab":1001, "media_resource.tab":367537, "taxon.tab":63374, "time_elapsed":{"sec":534.69, "min":8.91, "hr":0.15}}
nmnh_images	Thu 2020-12-24 06:05:58 AM	{"agent.tab":1001, "media_resource.tab":367537, "taxon.tab":63374, "time_elapsed":{"sec":509.55, "min":8.49, "hr":0.14}}
nmnh_images	Thu 2020-12-24 06:14:34 AM	{"agent.tab":1001, "media_resource.tab":347040, "taxon.tab":60121, "time_elapsed":{"sec":492.24, "min":8.2, "hr":0.14}}
nmnh_images	Sat 2020-12-26 07:00:04 AM	{"agent.tab":1001, "media_resource.tab":346792, "taxon.tab":60111, "time_elapsed":{"sec":509.26, "min":8.49, "hr":0.14}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHimagesAPI');
$timestart = time_elapsed();
$resource_id = 'nmnh_images';
$func = new NMNHimagesAPI($resource_id, false, true);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
?>