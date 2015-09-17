<?php
namespace php_active_record;
require_once( "sparqllib.php" );

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];
$mysqli_production = load_mysql_environment('development');


function remove_old_normalized_data($graph_name, $limit){
	echo "Delete from graph_name = $graph_name \n";
	$delete_sparql = "WITH GRAPH  <". $graph_name . "> DELETE { ?s <http://eol.org/schema/terms/normalizedUnit> ?o. } WHERE { ?s <http://eol.org/schema/terms/normalizedUnit> ?o. } LIMIT " 
					  . $limit;
	sparql_query( $delete_sparql );
	$delete_sparql = "WITH GRAPH  <". $graph_name . "> DELETE { ?s <http://eol.org/schema/terms/normalizedValue> ?o. } WHERE { ?s <http://eol.org/schema/terms/normalizedValue> ?o. } LIMIT " 
					  . $limit;
	sparql_query( $delete_sparql );
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
remove_old_normalized_data($graph_name, $limit);	

exit;
?>