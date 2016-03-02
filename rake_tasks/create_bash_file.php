<?php
namespace php_active_record;
require_once( dirname(__FILE__) . "/../lib/sparqllib.php" );

include_once(dirname(__FILE__) . "/../config/environment.php");

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


$all_sources = sparql_query( "SELECT DISTINCT ?source ?source_2 WHERE { GRAPH <http://eol.org/traitbank> { ?trait a eol:trait . ?trait dc:source ?source . optional{?trait <source> ?source_2}}}" );
//exit if errors
if( !$all_sources ) { print "error" . sparql_errno() . ": " . sparql_error(). "\n"; exit; }
$fp = fopen(dirname(__FILE__)."/chuncks.bat","wb");
fwrite($fp, "#!/bin/bash\n");
$limit = 5000;
$arr_1 = array();
$index_1 = 0;
$arr_2 = array();
$index_2 = 0;
while( $source_row = sparql_fetch_array( $all_sources ) )
	{
		if (strpos($source_row["source"], 'http://eol.org/resources/') !== false){
			if(!in_array($source_row["source"], $arr_1)){
				$arr_1[$index_1] =  $source_row["source"];
				$index_1 +=1;
			}
		}
	    else if(count($source_row) > 2 && strpos($source_row["source_2"], 'http://eol.org/resources/') !== false){
			if(!in_array($source_row["source_2"], $arr_2)){
				$arr_2[$index_2] =  $source_row["source_2"];
				$index_2 +=1;
			}
	    }
	}
	$index = 0;
    while($index < $index_1){
		$all_measurements_result = sparql_query("SELECT (count(*) AS ?count) FROM <http://eol.org/traitbank> WHERE { ?trait dwc:measurementValue ?value .?trait dc:source <". $arr_1[$index] .">  }");
		if( $all_measurements_result ){ 
			$count = sparql_fetch_array($all_measurements_result);
			$count = $count['count'];
			$lines = ceil($count/$limit);
			for($x = 0; $x < $lines; $x++){
				$offset = $x * $limit;
				$content = "php ".dirname(__FILE__)."/add_normalized_measurements.php " . $arr_1[$index] . " " . $limit . " " . $offset . " " . "1\n";
				fwrite($fp, $content); 
			}
		}
    	$index +=1;	
    }
    $index = 0;
    while($index < $index_2){
		$all_measurements_result = sparql_query("SELECT (count(*) AS ?count) FROM <http://eol.org/traitbank> WHERE { ?trait dwc:measurementValue ?value .?trait <source> <". $arr_2[$index] .">  }");
		if( $all_measurements_result ){ 
			$count = sparql_fetch_array($all_measurements_result);
			$count = $count['count'];
			$lines = ceil($count/$limit);
			for($x = 0; $x < $lines; $x++){
				$offset = $x * $limit;
				$content = "php ".dirname(__FILE__)."/add_normalized_measurements.php " . $arr_2[$index] . " " . $limit . " " . $offset . " " . "2\n";
				fwrite($fp, $content); 
			}
		}
    	$index +=1;	
    }
    
    
fclose($fp);
chmod(dirname(__FILE__) . "/chuncks.bat", 0777);
exit;
?>
