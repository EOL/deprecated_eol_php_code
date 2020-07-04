<?php
namespace php_active_record;
/* This is a library that handles active DH.
First client is: DATA-1818: aggregate map data from descendants, and cap "size" of taxa that get maps
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DHConnLib');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

/*
$a[0] = array('a' => 'aa','b','c');
$a[1] = array('d','e');
$b[3] = array('f','g' => 'gg','h');
$c = array_merge($a, $b);
print_r($c);
exit("\n-end test-\n");
*/

//############################################################ start main
$resource_id = "1";
$func = new DHConnLib($resource_id);
$func->generate_children_of_taxa_from_DH(); //normal operation

/* Not part of normal operation. Just test. First client is Katie's image bundles. Works OK!
$func->initialize_get_ancestry_func();

$eol_id = '46564414'; //Gadus
if($ancestry = $func->get_ancestry_of_taxID($eol_id)) print_r($ancestry); //worked OK
else echo "\nNo ancestry\n";
if($children_json = $func->get_children_from_json_cache($eol_id, array(), true)) {
    $children = json_decode($children_json);
    print_r($children); //worked OK
}
else echo "\nNo children\n";
exit("\n-end-\n");
*/
//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
/*
Function run_diagnostics($resource_id) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
*/
?>