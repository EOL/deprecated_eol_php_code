<?php
namespace php_active_record;
/* 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

$resource_id = 'polytraits_new'; //this replaced the Polytraits.tar.gz resource from polytraits.php

// /* using mostly API and some HTML pages
require_library('connectors/ContributorsMapAPI');
require_library('connectors/PolytraitsNewAPI');
$func = new PolytraitsNewAPI($resource_id);
$func->start();

// $func->get_ancestry(1504); //works just test

// http://polytraits.lifewatchgreece.eu/taxon/Clymene+leiopygos/json/?exact=1&verbose=1&assoc=0
// $sciname = 'Clymene leiopygos';
// $sciname = 'Aricia (Scoloplos) fuscibranchis';
// $func->get_name_info($sciname); //just test
/*
http://polytraits.lifewatchgreece.eu/taxon/Aricia+%28Scoloplos%29+fuscibranchis/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Aricidea+%28Allia%29+claudiae/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Aricidea+%28Cirrophorus%29+furcata/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Autolytus+%28Autolytides%29+inermis/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+I/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+Ia/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+II/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+III/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+IIIa/json/?exact=1&verbose=1&assoc=0
http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+M/json/?exact=1&verbose=1&assoc=0
*/


unset($func);
exit("\n-stop muna-\n");
Functions::finalize_dwca_resource($resource_id, false, false, false);
// */

require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

if($undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'acceptedNameUsageID')) { //2nd param True means write to text file
    $arr['acceptedNameUsageID without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll acceptedNameUsageID have entries OK\n";

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