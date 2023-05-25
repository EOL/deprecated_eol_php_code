<?php
namespace php_active_record;
/* 
37	Wed 2023-04-05 05:56:16 AM	{"agent.tab":261, "media_resource.tab":22103, "reference.tab":2, "taxon.tab":67357, "vernacular_name.tab":41494, "time_elapsed":false}

usda_plants	Wed 2023-05-17 10:10:27 AM	{"agent.tab":344, "media_resource.tab":20149, "taxon.tab":17441, "vernacular_name.tab":16810, "time_elapsed":false}
usda_plants	Fri 2023-05-19 10:55:52 AM	{"agent.tab":344, "media_resource.tab":20149, "taxon.tab":93911, "vernacular_name.tab":44135, "time_elapsed":false}
usda_plants	Fri 2023-05-19 11:59:27 AM	{"agent.tab":344, "media_resource.tab":20149, "taxon.tab":93911, "vernacular_name.tab":44135, "time_elapsed":false}
usda_plants	Sat 2023-05-20 09:50:44 PM	        {"agent.tab":344, "media_resource.tab":20149, "taxon.tab":93911, "vernacular_name.tab":44135, "time_elapsed":false}
usda_plant_images	Tue 2023-05-23 06:24:58 AM	{"agent.tab":344, "media_resource.tab":20149, "taxon.tab":93911, "vernacular_name.tab":44135, "time_elapsed":false}

Hi Jen,
https://plants.usda.gov/home/plantProfile?symbol=ABAN
Question: Is it normal and should I follow it since the API provides it.
That a valid species-level taxon (e.g. Abronia angustifolia Greene) has two synonyms. A variety and a binomial.
- ABANA	Abronia angustifolia Greene var. arizonica (Standl.) Kearney & Peebles
- ABTO	Abronia torreyi Standl.
Another example: https://plants.usda.gov/home/plantProfile?symbol=ABGR
Thanks.

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
ini_set('memory_limit','7096M');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

$resource_id = 'usda_plant_images'; //this replaced the 37.tar.gz image resource from 37.php

/* works OK - get authorship of subspecies or variety
$name_str = "<i>Achnatherum occidentale</i> (Thurb.) Barkworth ssp. <i>californicum</i> (Merr. & Burtt Davy) Barkworth";
echo "\n[".$name_str."]\n";
if(preg_match_all("/<i>(.*?)<\/i>/ims", $name_str, $arr)) {
    $last = end($arr[1]);
    if(preg_match("/<i>".$last."<\/i>(.*?)elix/ims", $name_str."elix", $arr2)) {
        $author = trim($arr2[1]);
        echo "\n[".$author."]\n";
    }
}
exit("\n-end test-\n");
*/

// /* using Dumps
require_library('connectors/USDAPlantNewAPI');
$func = new USDAPlantNewAPI($resource_id);
$func->start();
unset($func);
// exit("\n-stop muna-\n");
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param true means delete folder

// */

require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
