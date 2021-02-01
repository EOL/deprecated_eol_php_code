<?php
namespace php_active_record;
/* estimated execution time: 17 minutes 

168	Saturday 2018-04-14 11:18:48 AM	{"agent.tab":98,  "media_resource.tab":129821, "taxon.tab":22302} - MacMini
168	Saturday 2018-04-14 11:37:37 AM	{"agent.tab":98,  "media_resource.tab":129821, "taxon.tab":22302} - eol-archive

Ran some tests if legacy connector still works: Not anymore, generates less records than usual
168	Sun 2021-01-31 08:50:38 PM	    {"agent.tab":107, "media_resource.tab":120280, "taxon.tab":7304, "time_elapsed":{"sec":1183.39, "min":19.72, "hr":0.33}} - MacMini

From resource_utility.php
168_meta_recoded	Thu 2021-01-14 07:53:44 AM	{"agent.tab":98, "media_resource.tab":129821, "taxon.tab":22302, "time_elapsed":{"sec":74.27, "min":1.24, "hr":0.02}} - eol-archive
*/

/*
### last run April 14 2018
### this will generate 168.tar.gz
#php5.6 168.php jenkins
For now only resource_utility.php is used to update the legacy 168.tar.gz.
*/
exit("\nLegacy connector must be updated first. Tried running it and it generated far less fewer than normal. So I stopped running it until investigated.\n");

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/BioImagesAPI');
$timestart = time_elapsed();
$resource_id = 168;
$func = new BioImagesAPI($resource_id, false, true);
$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //2nd param False - not a big file | 3rd param True - can delete working folder
?>