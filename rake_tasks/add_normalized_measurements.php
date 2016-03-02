<?php
namespace php_active_record;
require_once( dirname(__FILE__) . "/../lib/sparqllib.php" );

include_once(dirname(__FILE__) . "/../config/environment.php");

function insert_single_measurement($trait, $value, $unit){
	$norm_value = str_replace("\"","", Functions::normalize_value($value, $unit));
	$norm_unit = str_replace("\"","", Functions::normalize_unit($unit));
	$sparql_insert = "INSERT DATA INTO GRAPH <http://eol.org/traitbank> { ";
	if(trim($norm_unit) != ""){
		$sparql_insert .= " <" . $trait . "> <http://eol.org/schema/terms/normalizedValue> \"". $norm_value . "\"; <http://eol.org/schema/terms/normalizedUnit> <". $norm_unit . "> . \n";	
	}else{
		$sparql_insert .= " <" . $trait . "> <http://eol.org/schema/terms/normalizedValue> \"". $norm_value . "\" .\n";
	}	
	$sparql_insert .= "}";
	$result = sparql_query( $sparql_insert );
	if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
}

function insert_normalized_data_for_all_measurements($source_name, $measurements_limit,  $measurements_offset, $type){
	$index = 0;
	$unit = '';
	if($type == 1){
		$sparql_all_measurements = "SELECT * WHERE { GRAPH <http://eol.org/traitbank> { 
          ?trait dc:source <". $source_name ."> . 
          ?trait <http://rs.tdwg.org/dwc/terms/measurementValue> ?value .
          OPTIONAL {?trait <http://rs.tdwg.org/dwc/terms/measurementUnit> ?unit } .
          FILTER NOT EXISTS { ?trait <http://eol.org/schema/terms/normalizedValue> ?n_value } } } LIMIT " . $measurements_limit;	
	}else{
		$sparql_all_measurements = "SELECT * WHERE { GRAPH <http://eol.org/traitbank> { 
          ?trait <source> <". $source_name ."> . 
          ?trait <http://rs.tdwg.org/dwc/terms/measurementValue> ?value .
          OPTIONAL {?trait <http://rs.tdwg.org/dwc/terms/measurementUnit> ?unit } .
          FILTER NOT EXISTS { ?trait <http://eol.org/schema/terms/normalizedValue> ?n_value } } } LIMIT " . $measurements_limit;
	}
	
	$all_measurements_result = sparql_query( $sparql_all_measurements );
	// exit if errors 
	if( !$all_measurements_result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
	if ($measurements_offset == 0){
		echo "\n Insert into source_name = $source_name \n ";
	}
	while( $measurement_row = sparql_fetch_array( $all_measurements_result ) )
	{	
		$trait = $measurement_row['trait'];
		$value = $measurement_row['value'];
		if(count($measurement_row) == 6){
			$unit = $measurement_row['unit'];
		}
		insert_single_measurement($trait, $value, $unit);
		$index += 1;
		if ($index % 1000 == 0){
			echo ".";
		}
	}
}

$db = sparql_connect( SPARQL_ENDPOINT );
if( !$db ) { print "error" . sparql_errno() . ": " . sparql_error(). "\n"; exit; }

sparql_ns( "eol", "http://eol.org/schema/" );
sparql_ns( "eolterms", "http://eol.org/schema/terms/" );
sparql_ns( "eolreference", "http://eol.org/schema/reference/" );
sparql_ns( "dwc", "http://rs.tdwg.org/dwc/terms/" );
sparql_ns( "dwct", "http://rs.tdwg.org/dwc/dwctype/" );
sparql_ns( "dc", "http://purl.org/dc/terms/" );
sparql_ns( "rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#" );
sparql_ns( "rdfs", "http://www.w3.org/2000/01/rdf-schema#" );
sparql_ns( "foaf", "http://xmlns.com/foaf/0.1/" );
sparql_ns( "obis", "http://iobis.org/schema/terms/" );
sparql_ns( "owl", "http://www.w3.org/2002/07/owl#" );
sparql_ns( "anage", "http://anage.org/schema/terms/" );
sparql_ns( "xsd", "http://www.w3.org/2001/XMLSchema#" );


//echo "\n memory =  " . memory_get_peak_usage()."\n";
$source_name = @$argv[1];
$limit = @$argv[2];
$offset = @$argv[3];
$type = @$argv[4];
insert_normalized_data_for_all_measurements($source_name, $limit, $offset, $type);
//echo "\n memory =  " . memory_get_peak_usage()."\n";
exit;
?>
