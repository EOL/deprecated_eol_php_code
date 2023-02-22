<?php
namespace php_active_record;
/* DATA-1871: new NMNH image connector from GBIF export

Jenkins link: http://160.111.248.39:8081/job/NMNH_images/

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
nmnh_images	Thu 2021-10-14 08:28:15 AM	{"agent.tab":1305, "media_resource.tab":383808, "taxon.tab":69660, "time_elapsed":{"sec":1687.74, "min":28.13, "hr":0.47}}
nmnh_images	Sun 2021-10-24 10:34:21 PM	{"agent.tab":1305, "media_resource.tab":383808, "taxon.tab":69660, "time_elapsed":{"sec":1192.44, "min":19.87, "hr":0.33}}
nmnh_images	Mon 2021-10-25 12:41:55 AM	{"agent.tab":1305, "media_resource.tab":383808, "taxon.tab":69660, "time_elapsed":{"sec":1211.35, "min":20.19, "hr":0.34}}
nmnh_images	Mon 2021-10-25 01:45:07 AM	{"agent.tab":1297, "media_resource.tab":381102, "taxon.tab":68739, "time_elapsed":{"sec":1189.7, "min":19.83, "hr":0.33}}
nmnh_images	Mon 2021-10-25 05:13:05 AM	{"agent.tab":1295, "media_resource.tab":380510, "taxon.tab":68462, "time_elapsed":{"sec":1175.73, "min":19.6, "hr":0.33}}
nmnh_images	Fri 2022-01-14 08:16:11 AM	{"agent.tab":1328, "media_resource.tab":391395, "taxon.tab":70370, "time_elapsed":{"sec":1141.38, "min":19.02, "hr":0.32}}
nmnh_images	Tue 2022-07-19 03:53:41 AM	{"agent.tab":1346, "media_resource.tab":450266, "taxon.tab":89524, "time_elapsed":{"sec":1536.04, "min":25.6, "hr":0.43}} consistent inc. OK
less: with 'TAX CRT' or 'Taxa CRT' in description
less: blank StillImage value --- 101 recs below
[rec_type] => Array(
            [StillImage] => Array(
                    [image/jpeg] => 429345
                    [] => 101
                )
        )

nmnh_images	Mon 2022-11-28 04:42:22 AM	{"agent.tab":1356, "media_resource.tab":456528, "taxon.tab":90294, "time_elapsed":{"sec":1469.41, "min":24.49, "hr":0.41}}
Below start of only publicdomain licensed media is allowed:
nmnh_images	Mon 2022-11-28 08:54:38 AM	{"agent.tab":453, "media_resource.tab":274757, "taxon.tab":57940, "time_elapsed":{"sec":1399.95, "min":23.33, "hr":0.39}}
nmnh_images	Tue 2023-02-21 12:49:43 PM	{"agent.tab":452, "media_resource.tab":279631, "taxon.tab":60230, "time_elapsed":{"sec":1374.83, "min":22.91, "hr":0.38}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NMNHimagesAPI');
$timestart = time_elapsed();
$resource_id = 'nmnh_images';
$func = new NMNHimagesAPI($resource_id, false, true);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
?>