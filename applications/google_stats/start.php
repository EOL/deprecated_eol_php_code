#!/usr/local/bin/php
<?php
//#!/usr/local/bin/php
//
//exit;

define("ENVIRONMENT", "slave");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);
$path = "2009_07";
//=================================================================
$mysqli2 = load_mysql_environment('eol_statistics');
/*
initialize_tables($mysqli2);exit;
*/
//=================================================================
$query1 = "SELECT tcn.taxon_concept_id, n.string 
FROM taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id) JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) WHERE tcn.vern=0 AND tcn.preferred=1 AND tc.supercedure_id=0 AND tc.published=1 GROUP BY tcn.taxon_concept_id ORDER BY tcn.source_hierarchy_entry_id DESC 
limit 10; ";
$result = $mysqli->query($query1);    
$fields=array();
$fields[0]="taxon_concept_id";
$fields[1]="string";
$temp = save_to_txt($result,"hierarchies_names",$fields,$path);
//=================================================================
$query1 = "SELECT DISTINCT a.full_name, tcn.taxon_concept_id 
FROM agents a
JOIN agents_resources ar ON (a.id=ar.agent_id)
JOIN harvest_events he ON (ar.resource_id=he.resource_id)
JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id)
JOIN taxa t ON (het.taxon_id=t.id)
JOIN taxon_concept_names tcn ON (t.name_id=tcn.name_id)
WHERE a.full_name IN (
	'AmphibiaWeb', 'BioLib.cz', 'Biolib.de', 'Biopix', 'Catalogue of Life', 'FishBase',
	'Global Biodiversity Information Facility (GBIF)', 'IUCN', 'Micro*scope',
	'Solanaceae Source', 'Tree of Life web project', 'uBio','AntWeb','ARKive', 'The Nearctic Spider Database','Animal Diversity Web' ) 
limit 10; ";
$result = $mysqli->query($query1);    
$fields=array();
$fields[0]="full_name";
$fields[1]="taxon_concept_id";
$temp = save_to_txt($result,"agents_hierarchies",$fields,$path);

//=================================================================
$query1 = "SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id FROM page_names pn JOIN taxon_concept_names tcn ON (pn.name_id=tcn.name_id) 
LIMIT 0, 10;";
$result = $mysqli->query($query1);    
$fields=array();
$fields[0]="full_name";
$fields[1]="taxon_concept_id";
$temp = save_to_txt($result,"agents_hierarchies_bhl",$fields,$path);
//=================================================================

$update = $mysqli2->query("TRUNCATE TABLE eol_statistics.hierarchies_names");        
$update = $mysqli2->query("TRUNCATE TABLE eol_statistics.agents_hierarchies");        

//$pre_path = "C:/webroot/eol_php_code/applications/google_stats/data/2009_07";
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $path . "/hierarchies_names.txt' INTO TABLE eol_statistics.hierarchies_names");        
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $path . "/agents_hierarchies.txt' INTO TABLE eol_statistics.agents_hierarchies");        
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $path . "/agents_hierarchies_bhl.txt' INTO TABLE eol_statistics.agents_hierarchies");        

//=================================================================
//=================================================================
function initialize_tables()
{
	global $mysqli2;
	
	$query="DROP TABLE IF EXISTS `eol_statistics`.`agents_hierarchies`;
	CREATE TABLE  `eol_statistics`.`agents_hierarchies` (
	`agentName` varchar(64) NOT NULL,
	`hierarchiesID` int(10) unsigned NOT NULL,
	PRIMARY KEY  USING BTREE (`agentName`,`hierarchiesID`),
	KEY `hierarchiesID` (`hierarchiesID`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8";
	$update = $mysqli2->query($query);
	
	$query="DROP TABLE IF EXISTS `eol_statistics`.`google_analytics_page_statistics`;
	CREATE TABLE  `eol_statistics`.`google_analytics_page_statistics` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`taxon_id` int(10) unsigned default NULL,
	`url` varchar(1000) NOT NULL,
	`page_views` int(10) unsigned NOT NULL,
	`unique_page_views` int(10) unsigned NOT NULL,
	`time_on_page` time NOT NULL,
	`bounce_rate` float default NULL,
	`percent_exit` float default NULL,
	`money_index` float default NULL,
	`date_added` datetime NOT NULL,
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=240640 DEFAULT CHARSET=utf8";
	$update = $mysqli2->query($query);

	$query="DROP TABLE IF EXISTS `eol_statistics`.`hierarchies_names`;
	CREATE TABLE  `eol_statistics`.`hierarchies_names` (
	`hierarchiesID` int(10) unsigned NOT NULL,
	`scientificName` varchar(255) default NULL,
	`commonNameEN` varchar(255) default NULL,
	PRIMARY KEY  (`hierarchiesID`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8";
	$update = $mysqli2->query($query);

}

function save_to_txt($result,$filename,$fields,$path)
{
	$str="";
	while($result && $row=$result->fetch_assoc())	
	{
		for ($i = 0; $i < count($fields); $i++) 		
		{
			$field = $fields[$i];
			$str .= $row["$field"] . chr(9);
		}
		$str .= "\n";
	}
	$filename = "data/" . $path . "/$filename" . ".txt";
	if($fp = fopen($filename,"w")){fwrite($fp,$str);fclose($fp);}		
}

/*
//$query1 .= " INTO OUTFILE 'C:/webroot/eol_php_code/applications/google_stats/data/2009_07/eli.txt' FIELDS TERMINATED BY '\t' ";
*/

?>