<?php
namespace php_active_record;
/* https://content.eol.org/resources/549
estimated execution time: 17 minutes 
168	Saturday 2018-04-14 11:18:48 AM	{"agent.tab":98,  "media_resource.tab":129821, "taxon.tab":22302} - MacMini
168	Saturday 2018-04-14 11:37:37 AM	{"agent.tab":98,  "media_resource.tab":129821, "taxon.tab":22302} - eol-archive

Ran some tests if legacy connector still works: Not anymore, generates less records than usual
168	Sun 2021-01-31 08:50:38 PM	    {"agent.tab":107, "media_resource.tab":120280, "taxon.tab":7304, "time_elapsed":{"sec":1183.39, "min":19.72, "hr":0.33}} - MacMini

168	Wed 2023-02-08 03:30:21 AM	    {"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22938, "time_elapsed":{"sec":171.51, "min":2.86, "hr":0.05}}
168	Thu 2023-02-09 05:33:41 AM	    {"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22938, "time_elapsed":{"sec":134.1, "min":2.24, "hr":0.04}}
168	Thu 2023-02-16 10:12:13 AM	    {"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22937, "time_elapsed":{"sec":115.79, "min":1.93, "hr":0.03}}
168	Wed 2023-02-22 05:24:03 AM	    {"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22937, "time_elapsed":{"sec":238.83, "min":3.98, "hr":0.07}}
Below removed < 200 bytes size images (blank images) - Eli's initiative.
168	Wed 2023-02-22 06:38:53 AM	    {"agent.tab":121, "media_resource.tab":144699, "taxon.tab":22937, "time_elapsed":{"sec":122.13, "min":2.04, "hr":0.03}}

From resource_utility.php
168_meta_recoded	Thu 2021-01-14 07:53:44 AM	{"agent.tab":98,  "media_resource.tab":129821, "taxon.tab":22302, "time_elapsed":{"sec":74.27, "min":1.24, "hr":0.02}} - eol-archive
168_meta_recoded	Wed 2023-02-08 03:31:38 AM	{"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22938, "time_elapsed":{"sec":75.23, "min":1.25, "hr":0.02}}
168_meta_recoded	Thu 2023-02-09 05:34:55 AM	{"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22938, "time_elapsed":{"sec":71.92, "min":1.2, "hr":0.02}}
168_meta_recoded	Thu 2023-02-16 10:13:29 AM	{"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22937, "time_elapsed":{"sec":74.1, "min":1.24, "hr":0.02}}
168_meta_recoded	Wed 2023-02-22 05:25:17 AM	{"agent.tab":121, "media_resource.tab":144845, "taxon.tab":22937, "time_elapsed":{"sec":73.24, "min":1.22, "hr":0.02}}
Below removed < 200 bytes size images (blank images) - Eli's initiative.
168_meta_recoded	Wed 2023-02-22 06:40:06 AM	{"agent.tab":121, "media_resource.tab":144699, "taxon.tab":22937, "time_elapsed":{"sec":71.15, "min":1.19, "hr":0.02}}
*/

/* Jenkins entry:
cd /html/eol_php_code/update_resources/connectors

### this will generate 168.tar.gz
php5.6 168.php jenkins

### this will generate 168_meta_recoded.tar.gz
php5.6 resource_utility.php jenkins '{"resource_id": "168_meta_recoded", "task": "metadata_recoding"}'

#LAST STEP: copy last transactional DwCA to Bioimages.tar.gz OK
cd /html/eol_php_code/applications/content_server/resources
cp 168_meta_recoded.tar.gz Bioimages.tar.gz
ls -lt 168_meta_recoded.tar.gz
ls -lt Bioimages.tar.gz
rm -f 168_meta_recoded.tar.gz

cd /html/eol_php_code/update_resources/connectors
php5.6 ckan_api_access.php jenkins "5b1ebec7-efd0-47b5-860e-5c841d88d366"
*/

/*
These 2 images should now be for BI-taxon-118153 and not BI-taxon-127589. If so you are using the latest dump file from Malcolm Storey.
BI-image-58316	BI-taxon-127589	http://purl.org/dc/dcmitype/StillImage	image/jpeg		Dorsal view	Longitude (deg): -1.2. Latitude (deg): 51.4. Longitude (deg/min): 1Â° 10' W. Latitude (deg/min): 51Â° 20' N. Vice county name: Berks. Vice county no.: 22. Country: England. Stage: Imago. Identified by: Malcolm Storey. Comment: Entered house on hot sunny day. Category: standard photograph or close-up. Photographic equipment used: Nikon D100 dSLR with Tamron SP T90 AF Macro 1:1 lens.	http://www.discoverlife.org/mp/20p?img=I_MWS60624&res=mx	http://www.bioimages.org.uk/html/../image.php?id=58316			en	Everyone	http://creativecommons.org/licenses/by-nc-sa/3.0/			fd27e41d78ff05c48d1f010deaaf3cde
BI-image-58315	BI-taxon-127589	http://purl.org/dc/dcmitype/StillImage	image/jpeg		Ventral view	Longitude (deg): -1.2. Latitude (deg): 51.4. Longitude (deg/min): 1Â° 10' W. Latitude (deg/min): 51Â° 20' N. Vice county name: Berks. Vice county no.: 22. Country: England. Stage: Imago. Identified by: Malcolm Storey. Comment: Entered house on hot sunny day. Category: standard photograph or close-up. Photographic equipment used: Nikon D100 dSLR with Tamron SP T90 AF Macro 1:1 lens.	http://www.discoverlife.org/mp/20p?img=I_MWS60623&res=mx	http://www.bioimages.org.uk/html/../image.php?id=58315			en	Everyone	http://creativecommons.org/licenses/by-nc-sa/3.0/			fd27e41d78ff05c48d1f010deaaf3cde
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BioImagesAPI');
$timestart = time_elapsed();
$resource_id = 168;
$func = new BioImagesAPI($resource_id, false, true);

/* test only
// Always add Malcolm Storey as Owner:
$owner_array = explode(",", "Malcolm Storey, Eli Agbayani");
$owner_array[] = "Malcolm Storey";
$owner_array = array_map('trim', $owner_array);
$owner_array = array_filter($owner_array); //remove null arrays
$owner_array = array_unique($owner_array); //make unique
$owner_array = array_values($owner_array); //reindex key
$ret = Functions::remove_whitespace(implode(", ", $owner_array));
echo "\n[$ret]\n";
exit("\nend tests\n");
*/

// /* main operation
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
// */

/* test func
$url = "http://www.discoverlife.org/mp/20p?img=I_MWS10894&res=mx";
$url = "http://www.discoverlife.org/mp/20p?img=I_MWS46466&res=mx";
$url = "http://www.discoverlife.org/mp/20p?img=I_MWS17704&res=mx";
$media_url = $func->download_img_then_use_local_file_as_path($url);
echo "\nmedia_url: [$media_url]\n";
*/
?>