<?php
namespace php_active_record;
/* this is a utility to test Pensoft annotation */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

$timestart = time_elapsed();

require_library('connectors/Functions_Pensoft');
require_library('connectors/Pensoft2EOLAPI');

$param = array("task" => "generate_eol_tags_pensoft", "resource" => "all_BHL", "resource_id" => "TreatmentBank", "subjects" => "Uses");

$func = new Pensoft2EOLAPI($param);
$desc = "(crops: peanuts, rice, sugarcane); (littoral: dune); (nest/prey: mud dauber nest [f]); (orchard: grapefruit, orange); (plants: bluebonnets, Indian paintbrush, miscellaneous vegetation, vegetation, next to cotton field); (soil/woodland: saltcedar)";
$desc = "SAIAB 60874  , 19  (of 23) specimens, SL 6.6–9.8 cm, Mozambique: Zambezi System : Zambezi River: island bank off the Marromeu harbour, 18 ◦ 17 ′ 08.63 ′′ S, 35 ";

/* option 1 works, but it skips a lot of steps that is needed in real-world connector run.
$json = $func->run_partial($desc);
$arr = json_decode($json); print_r($arr);
*/

/* option 2 --- didn't get to work
$basename = "ile_-_173"."ice";
$desc = strip_tags($desc);
$desc = trim(Functions::remove_whitespace($desc));
// $func->results = array();
$arr = $func->retrieve_annotation($basename, $desc); //it is in this routine where the pensoft annotator is called/run
// $arr = json_decode($json); 
print_r($arr);
*/

// /* option 2 from AntWebAPI.php --- worked OK!
// /* This is used for accessing Pensoft annotator to get ENVO URI given habitat string.
$param['resource_id'] = 24; //AntWeb resource ID
require_library('connectors/Functions_Pensoft');
require_library('connectors/Pensoft2EOLAPI');
$pensoft = new Pensoft2EOLAPI($param);
$pensoft->initialize_remaps_deletions_adjustments();
// /* to test if these 4 variables are populated.
echo "\n From Pensoft Annotator:";
echo("\n remapped_terms: "              .count($pensoft->remapped_terms)."\n");
echo("\n mRemarks: "                    .count($pensoft->mRemarks)."\n");
echo("\n delete_MoF_with_these_labels: ".count($pensoft->delete_MoF_with_these_labels)."\n");
echo("\n delete_MoF_with_these_uris: "  .count($pensoft->delete_MoF_with_these_uris)."\n");
// exit;
$final = array();
$basename = md5($desc);
$desc = strip_tags($desc);
$desc = trim(Functions::remove_whitespace($desc));
$pensoft->results = array();
if($arr = $pensoft->retrieve_annotation($basename, $desc)) {
    print_r($arr);
}
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>