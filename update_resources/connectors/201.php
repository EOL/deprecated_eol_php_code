<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/DATA-1551

Before partner provides a TSV file:
estimated execution time: 14 minutes: 40k images | 50 minutes: 72k images

Now partner provides/hosts a DWC-A file. Together with images they also now share text objects as well.
estimated execution time: 55 minutes for:
                                                5Jan'15     28Jan'15
 images:                114,658     114,103     138,879     138,878
 measurementorfact:     201,088     201,088     239,256     106,336
 occurrences                                                26,154
 taxa:                  17,499      17,499      19,627      19,627

====================================================== how to run:
php5.6 201.php jenkins
#generates 201.tar.gz

php5.6 resource_utility.php jenkins '{"resource_id": "201_meta_recoded", "task": "metadata_recoding"}'
#generates 201_meta_recoded.tar.gz

php5.6 resource_utility.php jenkins '{"resource_id": "201_meta_recoded_2", "task": "metadata_recoding"}'
#generates 201_meta_recoded_2.tar.gz
====================================================== END

201	Tuesday 2018-02-27 09:28:41 AM	{"measurement_or_fact.tab":129220, "media_resource.tab":180170, "occurrence.tab":31424,"taxon.tab":25467}
201	Wednesday 2018-03-21 12:24:43 AM{"measurement_or_fact.tab":130476, "media_resource.tab":181033, "occurrence.tab":31733,"taxon.tab":25494} no measurementID yet
201	Wednesday 2018-03-21 12:48:40 AM{"measurement_or_fact.tab":128349, "media_resource.tab":181033, "occurrence.tab":31733,"taxon.tab":25494} with measurementID and unique at that.
201	Mon 2020-09-14 02:15:07 PM	    {"measurement_or_fact.tab":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":487.16, "min":8.119999999999999, "hr":0.14}}
201	Wed 2020-10-14 02:15:39 PM	    {"measurement_or_fact.tab":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":518.17, "min":8.640000000000001, "hr":0.14}}
201	Thu 2021-06-03 10:05:20 AM	    {"measurement_or_fact.tab":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":493.95, "min":8.23, "hr":0.14}}
201_meta_recoded	Thu 2021-06-03 10:08:51 AM	{"measurement_or_fact_specific.tab":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":210.55, "min":3.51, "hr":0.06}}
201_meta_recoded_2	Thu 2021-06-03 10:12:23 AM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":212.65, "min":3.54, "hr":0.06}}

201	Fri 2021-06-04 03:06:12 AM	    {"measurement_or_fact.tab":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":491.96, "min":8.199999999999999, "hr":0.14}}
201	Mon 2022-10-03 11:12:32 PM	    {"measurement_or_fact.tab":195703, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":502.04, "min":8.369999999999999, "hr":0.14}}

201_meta_recoded	Fri 2021-06-04 03:09:46 AM	{"measurement_or_fact_specific.tab":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":212.97, "min":3.55, "hr":0.06}}
201_meta_recoded	Wed 2022-09-28 12:03:24 PM	{"measurement_or_fact_specific.tab":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":240.85, "min":4.01, "hr":0.07}}
201_meta_recoded	Mon 2022-10-03 11:16:14 PM	{"measurement_or_fact_specific.tab":148096, "media_resource.tab":204028, "occurrence.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":221.33, "min":3.69, "hr":0.06}}

201_meta_recoded_2	Fri 2021-06-04 03:13:18 AM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":211.77, "min":3.53, "hr":0.06}}
201_meta_recoded_2	Tue 2021-06-08 06:18:58 AM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":220.44, "min":3.67, "hr":0.06}}
201_meta_recoded_2	Wed 2022-09-28 12:07:22 PM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":237.83, "min":3.96, "hr":0.07}}
201_meta_recoded_2	Thu 2022-09-29 10:46:21 AM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":212.58, "min":3.54, "hr":0.06}}
201_meta_recoded_2	Mon 2022-10-03 11:19:51 PM	{"agent.tab":29, "measurement_or_fact.tab":290917, "media_resource.tab":204028, "occurrence_specific.tab":47607, "taxon.tab":28808, "time_elapsed":{"sec":213.9, "min":3.57, "hr":0.06}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/MCZHarvardArchiveAPI');

$timestart = time_elapsed();
$resource_id = 201;
$func = new MCZHarvardArchiveAPI($resource_id);

$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
// $func->get_mediaURL_for_first_40k_images(); //this is a utility
?>