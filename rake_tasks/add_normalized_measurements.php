<?php
namespace php_active_record;
require_once( "sparqllib.php" );

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$mysqli_production = load_mysql_environment('development');

function insert_normalized_data_for_single_measurement($graph_name, $data_point_uri, $value, $unit, $index){
	$norm_value = Functions::normalize_value($value, $unit);
	$norm_unit = Functions::normalize_unit($unit);
	
	if ($data_point_uri != ''){
		if ($index == 0) echo "Insert into graph_name = $graph_name \n";
		$sparql_insert = "INSERT DATA INTO <" . $graph_name . "> {
					<" . $data_point_uri . "> a <http://rs.tdwg.org/dwc/terms/MeasurementOrFact>;
					<http://eol.org/schema/terms/normalizedValue> ". SparqlClient::enclose_value(str_replace("\"","", $norm_value)) . ";
					<http://eol.org/schema/terms/normalizedUnit> ". SparqlClient::enclose_value(str_replace("\"","", $norm_unit)) . " }";
		sparql_query( $sparql_insert );
		$index += 1;
		if ($index % 1000 == 0) echo "."; 
		
	}
	$norm_value = null; unset($norm_value);
	$norm_unit = null; unset($norm_unit);
	$sparql_insert = null; unset($sparql_insert);
	return $index;
}


function insert_normalized_data_for_all_measurements($graph_name, $measurements_limit,  $measurements_offset){
	$index = 0;
	$unit = '';
	$sparql_all_measurements = "SELECT * FROM <" . $graph_name . "> WHERE { ?data_point_uri dwc:measurementValue ?value .
            OPTIONAL { ?data_point_uri dwc:measurementUnit ?unit_of_measure_uri } . } LIMIT " . $measurements_limit . " OFFSET " . $measurements_offset;
	$all_measurements_result = sparql_query( $sparql_all_measurements );
	
	// exit if errors 
	if( !$all_measurements_result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }

	// $index = insert_normalized_data_for_bulk_of_measurement($all_measurements_result, $graph_name, $index);
	while( $measurement_row = sparql_fetch_array( $all_measurements_result ) )
	{	
		$data_point_uri = $measurement_row['data_point_uri'];
		$value = $measurement_row['value'];
		if (count($measurement_row) == 6){
			$unit = $measurement_row['unit_of_measure_uri'];
		}					
		$index = insert_normalized_data_for_single_measurement($graph_name, $data_point_uri, $value, $unit, $index);
	}
}

function remove_old_normalized_data($graph_name){
	echo "Delete from graph_name = $graph_name \n";
	$delete_sparql = "DELETE FROM <". $graph_name . "> { ?s <http://eol.org/schema/terms/normalizedUnit> ?o } WHERE { ?s <http://eol.org/schema/terms/normalizedUnit> ?o }";
	$o = sparql_query( $delete_sparql );
	$o = null; unset($o);
	$delete_sparql = "DELETE FROM <". $graph_name . "> { ?s <http://eol.org/schema/terms/normalizedValue> ?o } WHERE { ?s <http://eol.org/schema/terms/normalizedValue> ?o }";
	$o = sparql_query( $delete_sparql );
	$o = null; unset($o);
	$delete_sparql= null; unset($delete_sparql);
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

echo "\n memory =  " . memory_get_peak_usage()."\n";
$graph_name = @$argv[1];
$limit = @$argv[2];
$offset = @$argv[3];
if($offset == 0){
	remove_old_normalized_data($graph_name);	
}
insert_normalized_data_for_all_measurements($graph_name,$limit,$offset);
echo "\n memory =  " . memory_get_peak_usage()."\n";

exit;
?>