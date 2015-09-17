<?php
namespace php_active_record;
require_once( "sparqllib.php" );

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$mysqli_production = load_mysql_environment('development');

function insert_normalized_data_for_single_measurement($graph_name, $data_point_uri, $value, $unit, $index){
	if ($unit && trim($unit) != ""){
		$value = Functions::normalize_value($value, $unit);
		$unit = Functions::normalize_unit($unit);
	}
	if ($data_point_uri != ''){
		if ($index == 0) echo "\n Insert into graph_name = $graph_name \n";
		$sparql_insert = "INSERT DATA INTO <" . $graph_name . "> {
					<" . $data_point_uri . "> a <http://rs.tdwg.org/dwc/terms/MeasurementOrFact>;
					<http://eol.org/schema/terms/normalizedValue> ". SparqlClient::enclose_value(str_replace("\"","", $value)) ;
		if ($unit && trim($unit) != ""){
			$sparql_insert = $sparql_insert . "; <http://eol.org/schema/terms/normalizedUnit> ". SparqlClient::enclose_value(str_replace("\"","", $unit)). " }";  
		} else {
			$sparql_insert = $sparql_insert . " }";
		}
		sparql_query( $sparql_insert );
		$index += 1;
		if ($index % 1000 == 0) echo "."; 
		
	}
	$sparql_insert = null; unset($sparql_insert);
	return $index;
}


function insert_normalized_data_for_all_measurements($graph_name, $measurements_limit){
	$index = 0;
	$unit = '';

	$sparql_all_measurements = "SELECT DISTINCT(?data_point_uri) ?value ?unit_of_measure_uri WHERE { GRAPH <" . $graph_name . "> { ?data_point_uri dwc:measurementValue ?value .
            OPTIONAL { ?data_point_uri dwc:measurementUnit ?unit_of_measure_uri } .
			OPTIONAL { ?data_point_uri eolterms:normalizedValue ?normalized_value } .
    		FILTER ( !bound(?normalized_value)) } } LIMIT " . $measurements_limit;	
	
	$all_measurements_result = sparql_query( $sparql_all_measurements );
	
	// exit if errors 
	if( !$all_measurements_result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
	while( $measurement_row = sparql_fetch_array( $all_measurements_result ) )
	{	 
		$data_point_uri = $measurement_row['data_point_uri'];
		$value = strtr($measurement_row['value'],"\x1E\x06", " ");
		$unit = '';
		if (count($measurement_row) == 6){
			$unit = strtr($measurement_row['unit_of_measure_uri'],"\x1E\x06", " ");
		}	
		if($data_point_uri && $value){			
			$index = insert_normalized_data_for_single_measurement($graph_name, $data_point_uri, $value, $unit, $index);
		}
	}
}


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

$graph_name = @$argv[1];
$limit = @$argv[2];

echo "\n" . date('l jS \of F Y h:i:s A') . "\n";
insert_normalized_data_for_all_measurements($graph_name, $limit);

exit;
?>
