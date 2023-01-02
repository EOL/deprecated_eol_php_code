<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/COLLAB-1006 */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

require_library('connectors/CypherQueryAPI');
$resource_id = 'eol';
$func = new CypherQueryAPI($resource_id);

/*
$arr["params"] = array("citation" => "the quick brown fox");
$arr["type"] = "wikidata_base_qry_citation";
$json = json_encode($arr);
print_r($arr);
print("\n".$json."\n");

exit("\n-end test\n");
*/

/* copied template - but works OK
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
print_r($arr);
*/



/* good example
$input["params"] = array("source" => "https://doi.org/10.1111/j.1469-185X.1984.tb00411.x");
$input["type"] = "wikidata_base_qry_source";
print_r($input); 
$func->query_trait_db($input);
*/

// /* good example
$citation = "J. Kuijt, B. Hansen. 2014. The families and genera of vascular plants. Volume XII; Flowering Plants: Eudicots - Santalales, Balanophorales. K. Kubitzki (ed). Springer Nature";
$input["params"] = array("citation" => $citation);
$input["type"] = "wikidata_base_qry_citation";
print_r($input); 
$func->query_trait_db($input);
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>
