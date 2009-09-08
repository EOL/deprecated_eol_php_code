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

$year_month = "2009_04";

$google_analytics_page_statistics = "google_analytics_page_statistics_" . $year_month;

//=================================================================
$mysqli2 = load_mysql_environment('eol_statistics');
/* use to initialize 3 tables - run once */ //initialize_tables($mysqli2);exit;

//=================================================================
$query = "SELECT tcn.taxon_concept_id, n.string FROM taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id) JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) WHERE tcn.vern=0 AND tcn.preferred=1 AND tc.supercedure_id=0 AND tc.published=1 GROUP BY tcn.taxon_concept_id ORDER BY tcn.source_hierarchy_entry_id DESC "; 
$query .= " limit 5 ";
$result = $mysqli->query($query);    
$fields=array();
$fields[0]="taxon_concept_id";
$fields[1]="string";
$temp = save_to_txt($result,"hierarchies_names",$fields,$year_month,chr(9),0,"txt");
//=================================================================
$query = "SELECT DISTINCT a.full_name, tcn.taxon_concept_id 
FROM agents a
JOIN agents_resources ar ON (a.id=ar.agent_id)
JOIN harvest_events he ON (ar.resource_id=he.resource_id)
JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id)
JOIN taxa t ON (het.taxon_id=t.id)
JOIN taxon_concept_names tcn ON (t.name_id=tcn.name_id)
WHERE a.full_name IN (
	'AmphibiaWeb', 'BioLib.cz', 'Biolib.de', 'Biopix', 'Catalogue of Life', 'FishBase',
	'Global Biodiversity Information Facility (GBIF)', 'IUCN', 'Micro*scope',
	'Solanaceae Source', 'Tree of Life web project', 'uBio','AntWeb','ARKive', 'The Nearctic Spider Database','Animal Diversity Web' ) ";
$query .= " limit 5 ";
$result = $mysqli->query($query);    
$fields=array();
$fields[0]="full_name";
$fields[1]="taxon_concept_id";
$temp = save_to_txt($result,"agents_hierarchies",$fields,$year_month,chr(9),0,"txt");
//=================================================================
$query = "SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id FROM page_names pn JOIN taxon_concept_names tcn ON (pn.name_id=tcn.name_id) ";
$query .= " LIMIT 5 ";
$result = $mysqli->query($query);    
$fields=array();
$fields[0]="full_name";
$fields[1]="taxon_concept_id";
$temp = save_to_txt($result,"agents_hierarchies_bhl",$fields,$year_month,chr(9),0,"txt");


//=================================================================

$update = $mysqli2->query("TRUNCATE TABLE eol_statistics.hierarchies_names");        
$update = $mysqli2->query("TRUNCATE TABLE eol_statistics.agents_hierarchies");        

$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/hierarchies_names.txt' INTO TABLE eol_statistics.hierarchies_names");        
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies.txt' INTO TABLE eol_statistics.agents_hierarchies");        
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies_bhl.txt' INTO TABLE eol_statistics.agents_hierarchies");        

//=================================================================
//start query9
$query="SELECT (SELECT COUNT(*) FROM eol_statistics.hierarchies_names) all_taxa_count, agentName, COUNT(*) agent_taxa_count FROM eol_statistics.agents_hierarchies GROUP BY agentName ORDER BY agentName;";
$result = $mysqli->query($query);    
$fields=array();
$fields[0]="all_taxa_count";
$fields[1]="agentName";
$fields[2]="agent_taxa_count";
$temp = save_to_txt($result,"query9",$fields,$year_month,",",1,"csv");
//end query9
//=================================================================
//start query10
$query="SELECT g.id, g.date_added, g.taxon_id, g.url, hn.scientificName, hn.commonNameEN, g.page_views, g.unique_page_views, TIME_TO_SEC(g.time_on_page) time_on_page_seconds, g.bounce_rate, g.percent_exit FROM eol_statistics." . $google_analytics_page_statistics . " g LEFT OUTER JOIN eol_statistics.hierarchies_names hn ON hn.hierarchiesID = g.taxon_id WHERE g.date_added > ADDDATE(CURDATE(), -1) ORDER BY page_views DESC, unique_page_views DESC, time_on_page_seconds DESC; ";
$result = $mysqli->query($query);    
$fields=array();
$fields[]="id";
$fields[]="date_added";
$fields[]="taxon_id";
$fields[]="url";
$fields[]="scientificName";
$fields[]="commonNameEN";
$fields[]="page_views";
$fields[]="unique_page_views";
$fields[]="time_on_page_seconds";
$fields[]="bounce_rate";
$fields[]="percent_exit";
$temp = save_to_txt($result,"query10",$fields,$year_month,",",1,"csv");
//end query10
//=================================================================

//start query11 - site_statistics
$query="SELECT ah.agentName,g.taxon_id, hn.scientificName, hn.commonNameEN,SUM(g.page_views) total_page_views,SUM(g.unique_page_views) total_unique_page_views,SUM(TIME_TO_SEC(g.time_on_page)) total_time_on_page_seconds FROM eol_statistics." . $google_analytics_page_statistics . " g INNER JOIN eol_statistics.agents_hierarchies ah	ON ah.hierarchiesID = g.taxon_id LEFT OUTER JOIN eol_statistics.hierarchies_names hn ON hn.hierarchiesID=g.taxon_id WHERE g.date_added > ADDDATE(CURDATE(), -1) GROUP BY ah.agentName, g.taxon_id ORDER BY ah.agentName, total_page_views DESC, total_unique_page_views DESC, total_time_on_page_seconds DESC;";
$result = $mysqli->query($query);    
$fields=array();
$fields[]="agentName";
$fields[]="taxon_id";
$fields[]="scientificName";
$fields[]="commonNameEN";
$fields[]="total_page_views";
$fields[]="total_unique_page_views";
$fields[]="total_time_on_page_seconds";
$temp = save_to_txt($result,"site_statistics",$fields,$year_month,",",1,"csv");
//end query11

 
//=================================================================
//start query12
$query="SELECT distinct g.taxon_id FROM eol_statistics." . $google_analytics_page_statistics . " g WHERE g.date_added > ADDDATE(CURDATE(), -1) and g.taxon_id>0;";
$result = $mysqli->query($query);    
$fields=array();
$fields[]="taxon_id";
$temp = save_to_txt ($result,"query12",$fields,$year_month,",",1,"csv");
//end query12

//=================================================================

function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)
{
	$str="";    
    if($with_col_header)
    {
		for ($i = 0; $i < count($fields); $i++) 		
		{
			$field = $fields[$i];
			$str .= $field . $field_separator;    //chr(9) is tab
		}
		$str .= "\n";    
    }
    
	while($result && $row=$result->fetch_assoc())	
	{
		for ($i = 0; $i < count($fields); $i++) 		
		{
			$field = $fields[$i];
			$str .= $row["$field"] . $field_separator;    //chr(9) is tab
		}
		$str .= "\n";
	}
	$filename = "data/" . $year_month . "/temp/$filename" . "." . $file_extension;
	if($fp = fopen($filename,"w")){fwrite($fp,$str);fclose($fp);}		
    
}//function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)




function initialize_tables()
{
	global $mysqli2;

	$query="DROP TABLE IF EXISTS `eol_statistics`.`agents_hierarchies`;";                        $update = $mysqli2->query($query);    		
	$query="DROP TABLE IF EXISTS `eol_statistics`.`hierarchies_names`;";                         $update = $mysqli2->query($query);

	$query="CREATE TABLE  `eol_statistics`.`agents_hierarchies` ( `agentName` varchar(64) NOT NULL, `hierarchiesID` int(10) unsigned NOT NULL, PRIMARY KEY  USING BTREE (`agentName`,`hierarchiesID`), KEY `hierarchiesID` (`hierarchiesID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8"; $update = $mysqli2->query($query);    
	$query="CREATE TABLE  `eol_statistics`.`hierarchies_names` ( `hierarchiesID` int(10) unsigned NOT NULL, `scientificName` varchar(255) default NULL, `commonNameEN` varchar(255) default NULL, PRIMARY KEY  (`hierarchiesID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8"; $update = $mysqli2->query($query);
    
}//function initialize_tables()

function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}


/*
//$query1 .= " INTO OUTFILE 'C:/webroot/eol_php_code/applications/google_stats/data/2009_07/eli.txt' FIELDS TERMINATED BY '\t' ";
*/

?>