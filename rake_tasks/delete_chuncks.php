<?php
namespace php_active_record;
require_once( "sparqllib.php" );

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$mysqli_production = load_mysql_environment('development');

$db = sparql_connect( SPARQL_ENDPOINT );
if( !$db ) { print "error" . sparql_errno() . ": " . sparql_error(). "\n"; exit; }

sparql_ns( "eol","http://eol.org/schema/" );
sparql_ns( "eolterms","http://eol.org/schema/terms/" );
sparql_ns( "eolreference","http://eol.org/schema/reference/" );
sparql_ns( "dwc","http://rs.tdwg.org/dwc/terms/" );
sparql_ns( "dwct","http://rs.tdwg.org/dwc/dwctype/" );
sparql_ns( "dc","http://purl.org/dc/terms/" );
sparql_ns( "rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#" );
sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );
sparql_ns( "foaf","http://xmlns.com/foaf/0.1/" );
sparql_ns( "obis","http://iobis.org/schema/terms/" );
sparql_ns( "owl","http://www.w3.org/2002/07/owl#" );
sparql_ns( "anage","http://anage.org/schema/terms/" );

$sparql_all_graphs = "SELECT ?graph WHERE { GRAPH ?graph { ?s ?p ?o } } GROUP BY ?graph";
$graphs_result = sparql_query( $sparql_all_graphs ); 
//exit if errors
if( !$graphs_result ) { print "error" . sparql_errno() . ": " . sparql_error(). "\n"; exit; }
$fp = fopen(dirname(__FILE__)."/delete_chuncks.bat","wb");
fwrite($fp, "#!/bin/bash\n");
$limit = 5000;
while( $graph_row = sparql_fetch_array( $graphs_result ) )
	{
		$graph_name = $graph_row["graph"];
		if(!strpos($graph_name,'mappings') && !strpos($graph_name,'eol_taxa')&& !strpos($graph_name,'schema') && strpos($graph_name, "eol.org/resources/") !== false){
			$sparql_all_measurements = "SELECT (count(*) AS ?count) FROM <" . $graph_name . "> WHERE { ?data_point_uri dwc:measurementValue ?value .
            							OPTIONAL { ?data_point_uri dwc:measurementUnit ?unit_of_measure_uri } . }";
			$all_measurements_result = sparql_query( $sparql_all_measurements );
			if( $all_measurements_result ){ 
				$measurement_count = sparql_fetch_array( $all_measurements_result );
				$count = $measurement_count['count'];
				$lines = ceil($count/$limit);
				for($x = 0; $x < $lines; $x++){
					$content = "php ".dirname(__FILE__)."/delete_normalized_measurements.php " . $graph_name . " " . $limit . "\n";
					fwrite($fp, $content); 
				}
				
			}
		}
	}
fclose($fp);
chmod(dirname(__FILE__) . "/chuncks.bat", 0777);
exit;
?>