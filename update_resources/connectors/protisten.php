<?php
namespace php_active_record;
/* Protisten.de gallery - https://eol-jira.bibalex.org/browse/DATA-1802
https://editors.eol.org/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=protisten

php update_resources/connectors/protisten.php _ '{"expire_seconds": "1"}'       --- expires now, expires in 1 sec.
php update_resources/connectors/protisten.php _ '{"expire_seconds": "false"}'   --- doesn't expire
php update_resources/connectors/protisten.php _ '{"expire_seconds": "86400"}'   --- 60*60*24    = 1 day   = expires in 86400 seconds
php update_resources/connectors/protisten.php _ '{"expire_seconds": "2592000"}' --- 60*60*24*30 = 30 days = expires in 2592000 seconds

php5.6 protisten.php jenkins '{"expire_seconds": "1"}'       #--- expires now, expires in 1 sec.
php5.6 protisten.php jenkins '{"expire_seconds": "false"}'   #--- doesn't expire
php5.6 protisten.php jenkins '{"expire_seconds": "86400"}'   #--- 60*60*24    = 1 day   = expires in 86400 seconds
php5.6 protisten.php jenkins '{"expire_seconds": "2592000"}' #--- 60*60*24*30 = 30 days = expires in 2592000 seconds

after fixing sciname inclusion
protisten	Wed 2021-02-03 02:37:37 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1124, "time_elapsed":{"sec":2337.88, "min":38.96, "hr":0.65}}
protisten	Mon 2021-02-08 04:43:22 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1124, "time_elapsed":{"sec":2379.84, "min":39.66, "hr":0.66}}
Using list from partner, we now remove images from certain scientificNames.
protisten	Mon 2021-02-08 05:04:23 AM	{"agent.tab":1, "media_resource.tab":1841, "taxon.tab":1123, "time_elapsed":{"sec":39.49, "min":0.66, "hr":0.01}}
should add 1 taxon and 1 image
protisten	Tue 2021-02-09 08:46:28 AM	{"agent.tab":1, "media_resource.tab":1842, "taxon.tab":1125, "time_elapsed":{"sec":2386.3, "min":39.77, "hr":0.66}}
protisten	Mon 2021-02-22 11:49:41 AM	{"agent.tab":1, "media_resource.tab":1894, "taxon.tab":1136, "time_elapsed":{"sec":2389.3, "min":39.82, "hr":0.66}}
below: 114 images were excluded; doesn't exist remotely
protisten	Tue 2023-06-13 02:47:02 AM	{"agent.tab":1, "media_resource.tab":2409, "taxon.tab":1277, "time_elapsed":{"sec":1662.78, "min":27.71, "hr":0.46}}
protisten	Thu 2023-07-06 02:04:24 PM	{"agent.tab":1, "media_resource.tab":2540, "taxon.tab":1278, "time_elapsed":{"sec":5799.56, "min":96.66, "hr":1.61}}
protisten	Mon 2023-07-17 01:25:57 PM	{"agent.tab":1, "media_resource.tab":2526, "taxon.tab":1278, "time_elapsed":{"sec":5850.37, "min":97.51, "hr":1.63}}
[does not exist] => Array( as of Jul 17, 2023
            [https://www.protisten.de/gallery-ARCHIVE/pics/Ceramium-spec-Aufwuchs-040-100-P9259693-698-HID.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Chaetoceros-decipiens-016-100-2098642-649-KIF.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Chaetoceros-decipiens-025-100-2098650-656-KIF.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Choanocystis-aculeata-020-200-2-9012767-774-MKH.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Cyphoderia-ampulla-040-200-CombineZ-025-HVE.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Ephelota-gemmipara-025-100-A297296-313-1-HID.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Nuclearia-simplex-063-200-P6122294-297-DOS.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Polyarthra-remata-020-125-2-B137598-ASW.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Salpingoeca-amphoridium-040-200-2-9023486-507-1-ASW.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Salpingoeca-amphoridium-040-200-2-9023486-507-2-ASW.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Tetmemorus-granulatus-var-granulatus-040-160-2-B172406-427-Zellwand-WPT.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Tetmemorus-granulatus-var-granulatus-040-160-2-B172406-427-Transversal-WPT.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Tetmemorus-granulatus-var-granulatus-040-160-2-B172406-427-Kern-WPT.jpg] => 
            [https://www.protisten.de/gallery-ARCHIVE/pics/Scenedesmus-falcatus-040-160-P6020120-ODB.jpg] => 
        )
protisten	Thu 2023-08-10 03:34:46 AM	{"agent.tab":1, "media_resource.tab":2540, "taxon.tab":1279, "time_elapsed":{"sec":5745.96, "min":95.77, "hr":1.6}}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ one time reported to Jeremy:
protisten.de has been republished
e.g. This taxon (Closterium incurvum) has 3 images in DwCA
f4686d1cb464e70572f0d58ec00364c3 d4103073fb6feb109949bc120a73997f https://www.protisten.de/gallery-ARCHIVE/pics/Closterium-incurvum-063-200-2-7054999-HHW_NEW.jpg 
9fb3519c03d86ca5ee6895ff18803596 d4103073fb6feb109949bc120a73997f https://www.protisten.de/gallery-ARCHIVE/pics/Closterium-incurvum-040-100-P9274176-HID-INET800_NEW.jpg 
ffa94aed4fcd6977064ab7280635cdb3 d4103073fb6feb109949bc120a73997f https://www.protisten.de/gallery-ARCHIVE/pics/Closterium-spec-063-200-P8173128-145-grau-RLW-INET800_NEW.jpg 
Correctly listed in its media page:
https://eol.org/pages/920788/media?resource_id=697
BUT only one image is showing.
Do we need to truncate this resource before reharvest-republish steps?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ end */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "protisten";

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;
/* Array(
    [expire_seconds] => 86400
)*/

// /* un-comment in real operation
require_library('connectors/Protisten_deAPI');
$func = new Protisten_deAPI($resource_id, $param);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param true means to delete working resource folder
// */

/* test function
$func->get_stable_urls_info(); exit;
*/

/* utility */
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
// $func->check_unique_ids($resource_id); //takes time
$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id); // remove working dir

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>