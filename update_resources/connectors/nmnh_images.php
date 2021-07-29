<?php
namespace php_active_record;
/* DATA-1871: new NMNH image connector from GBIF export
nmnh_images	Thu 2020-12-24 05:28:07 AM	{"agent.tab":1001, "media_resource.tab":367537, "taxon.tab":63374, "time_elapsed":{"sec":534.69, "min":8.91, "hr":0.15}}
nmnh_images	Thu 2020-12-24 06:05:58 AM	{"agent.tab":1001, "media_resource.tab":367537, "taxon.tab":63374, "time_elapsed":{"sec":509.55, "min":8.49, "hr":0.14}}
nmnh_images	Thu 2020-12-24 06:14:34 AM	{"agent.tab":1001, "media_resource.tab":347040, "taxon.tab":60121, "time_elapsed":{"sec":492.24, "min":8.2, "hr":0.14}}
nmnh_images	Sat 2020-12-26 07:00:04 AM	{"agent.tab":1001, "media_resource.tab":346792, "taxon.tab":60111, "time_elapsed":{"sec":509.26, "min":8.49, "hr":0.14}}
Jenkins with API request and newly downloaded DwCA
nmnh_images	Mon 2020-12-28 10:24:51 AM	{"agent.tab":1001, "media_resource.tab":346792, "taxon.tab":60111, "time_elapsed":{"sec":475.05, "min":7.92, "hr":0.13}}
nmnh_images	Fri 2021-01-15 02:09:47 PM	{"agent.tab":1265, "media_resource.tab":468818, "taxon.tab":77802, "time_elapsed":{"sec":1094.13, "min":18.24, "hr":0.3}}
after adding a new filter for title field: 'Label'
nmnh_images	Sat 2021-01-30 09:04:23 PM	{"agent.tab":1240, "media_resource.tab":379156, "taxon.tab":73469, "time_elapsed":{"sec":909.76, "min":15.16, "hr":0.25}}
nmnh_images	Thu 2021-07-29 05:38:12 AM	{"agent.tab":1266, "media_resource.tab":380607, "taxon.tab":68393, "time_elapsed":{"sec":1006.78, "min":16.78, "hr":0.28}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHimagesAPI');
$timestart = time_elapsed();
$resource_id = 'nmnh_images';
$func = new NMNHimagesAPI($resource_id, false, true);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
?>