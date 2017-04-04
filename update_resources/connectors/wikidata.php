<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$resource_id = "1";

// /* //main operation
// $func = new WikiDataAPI($resource_id, "es");     //done final-es
// $func = new WikiDataAPI($resource_id, "fr");     //done final-fr
// $func = new WikiDataAPI("957", "de");            //done
// $func = new WikiDataAPI($resource_id, "ja");     //done
// $func = new WikiDataAPI($resource_id, "it");     //done
// $func = new WikiDataAPI($resource_id, "ru");     //done
// $func = new WikiDataAPI($resource_id, "ko");     //done
// $func = new WikiDataAPI($resource_id, "cu");     //done
// $func = new WikiDataAPI($resource_id, "uk");     //done
// $func = new WikiDataAPI($resource_id, "pl");     //done
// $func = new WikiDataAPI($resource_id, "zh");
// $func = new WikiDataAPI($resource_id, "pt");

// $func = new WikiDataAPI($resource_id, "en", "taxonomy"); //3rd param is boolean taxonomy; true means will generate hierarchy resource. [wikidata-hierarchy]    //done
$func = new WikiDataAPI($resource_id, "en", "wikimedia");     //done - Used for Commons
// $func = new WikiDataAPI($resource_id, "en");     //done

// $func = new WikiDataAPI($resource_id, "nl");
// $func = new WikiDataAPI($resource_id, "sv");    //still being run, many many bot inspired
// $func = new WikiDataAPI($resource_id, "vi");

//===================

// $func = new WikiDataAPI($resource_id, "ceb");



$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
// */

// /* utility
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
$func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>