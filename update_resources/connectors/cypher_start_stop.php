<?php
namespace php_active_record;
/* 
Katja's task:
https://docs.google.com/document/d/1OV7M5h-L7C4x5_B2YTKwimyJ68-pFQ9RjQx-Sqq1eDA/edit?userstoinvite=jen.hammock@gmail.com&sharingaction=manageaccess&role=writer 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = true; //orig value should be -> false ... especially in eol-archive server

/* copied template - not used but works OK
====================================================
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$arr = json_decode($params['json'], true);
====================================================
*/

/* also works here: which is a copied template from 'connectors/CypherQueryAPI'
require_library('connectors/CypherQueryAPI_StartStop');
$resource_id = 'eol';
$func = new CypherQueryAPI_StartStop();
*/

require_library('connectors/CypherQueryAPI');
$resource_id = 'eol';
$func = new CypherQueryAPI();

/* with resource_id --- query has an error
$input = array();
$input["params"] = array("resource_id" => $resource_id);
$input["type"] = "wikidata_base_qry_resourceID";
$input["per_page"] = 500; // 500 worked ok
$input["trait kind"] = "inferred_trait";
$func->query_trait_db($input);
*/
/*
$input = array();
$input["params"] = array();
$input["type"] = "traits_stop_at";
$input["per_page"] = 500; // 500 worked ok
$arr = $func->get_traits_stop_at($input);
*/

/*
Saved OK [/Volumes/Crucial_2TB/eol_cache/cypher_query/b7/17/b7172623b0095c0271e738f4eebc6fbc.json]
Saved OK [/Volumes/Crucial_2TB/eol_cache/cypher_query/60/69/6069cfe15ad7643e22e33ee52149f6d8.json]
 No. of rows: 0
*/

/*
https://query.wikidata.org/sparql?query=SELECT ?s WHERE {VALUES ?id {"3393129"} ?s wdt:P1566 ?id }
https://stackoverflow.com/questions/74244994/query-multiple-geonameids-in-sparql-query-on-wikidata
*/

// /* for individual resource IDs --- working OK
$input = array();
/* 1st client
$input["params"] = array("resource_id" => 753); // 753-Flora do Brasil | 822-Kubitzki
$input["type"] = "wikidata_base_qry_resourceID";
*/

// /* 3rd client
// $input["params"] = array("source" => "https://doi.org/10.1007/s13127-017-0350-6"); 		// not needed for 3rd client
// $input["params"] = array("resource_id" => 753); // 753-Flora do Brasil | 822-Kubitzki	// not needed for 3rd client

/*
$input["type"] = "katja_start_stop_nodes";
$input["trait kind"] = "reg_report";
*/

$input["type"] = "katja_m1_m2";
$input["trait kind"] = "reg_report_m1m2";


$input["per_page"] = 50;
$func->query_trait_db($input);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n elapsed time = " . $elapsed_time_sec/60/60/24 . " days";
echo "\n Done processing.\n";
?>