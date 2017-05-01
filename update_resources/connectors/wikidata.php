<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WikiDataAPI');
$timestart = time_elapsed();
$resource_id = "sv";


/* utility
$func = new WikiDataAPI($resource_id, "");
// $func->create_temp_files_based_on_wikimedia_filenames();     //create blank json files
$func->fill_in_temp_files_with_wikimedia_dump_data();        //fill-in those blank json files
exit("\n end1 \n");
*/

/* utility
$func = new WikiDataAPI($resource_id, "");
$func->process_wikimedia_txt_dump(); //initial verification of the wikimedia dump file
exit("\n end2 \n");
*/


// /* //main operation
// $func = new WikiDataAPI($resource_id, "es");     //done final-es
// $func = new WikiDataAPI($resource_id, "fr");     //done final-fr
// $func = new WikiDataAPI("957", "de");            //done final-de
// $func = new WikiDataAPI($resource_id, "ja");     //done final-ja
// $func = new WikiDataAPI($resource_id, "it");     //done final-it
// $func = new WikiDataAPI($resource_id, "ru");     //done final-ru
// $func = new WikiDataAPI($resource_id, "ko");     //done final-ko
// $func = new WikiDataAPI($resource_id, "cu");     //done final-cu ? investigate why so few...
// $func = new WikiDataAPI($resource_id, "uk");     //done final-uk
// $func = new WikiDataAPI($resource_id, "pl");     //done final-pl
// $func = new WikiDataAPI($resource_id, "zh");     //done final-zh
// $func = new WikiDataAPI($resource_id, "pt");     //done final
// $func = new WikiDataAPI($resource_id, "en");     //done final-en

// $func = new WikiDataAPI($resource_id, "en", "taxonomy"); //3rd param is boolean taxonomy; true means will generate hierarchy resource. [wikidata-hierarchy]    //done
$func = new WikiDataAPI($resource_id, "en", "wikimedia");     //done - Used for Commons - total taxa = 2,208,086

// not yet complete:
// $func = new WikiDataAPI($resource_id, "nl");     //496940
// $func = new WikiDataAPI($resource_id, "sv");     //317830    //still being run, many many bot inspired
$func = new WikiDataAPI($resource_id, "vi");     //459950

//===================

// $func = new WikiDataAPI($resource_id, "ceb");


$func->get_all_taxa();
Functions::finalize_dwca_resource($resource_id);
// */

/* final-pt
not defined parent [Q4674600]
not defined parent [Q18596649]
total undefined parent_id: 2
*/

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

/*
You uploaded: final-zh.tar.gz
    http://rs.tdwg.org/dwc/terms/taxon:Total: 90952
    http://purl.org/dc/dcmitype/Text: 77991
*/


?>