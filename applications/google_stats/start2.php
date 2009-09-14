#!/usr/local/bin/php
<?php
//#!/usr/local/bin/php
//
//exit;

define("ENVIRONMENT", "slave_215");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);

$month = get_val_var("month");
$year = get_val_var("year");


//$month = "04"; $year = "2009";

$month = substr(strval($month/100),2,2); //print $month;exit;

//$year_month = "2009_04";

$year_month = $year . "_" . $month;

$google_analytics_page_statistics = "google_analytics_page_statistics_" . $year_month;

//=================================================================
$mysqli2 = load_mysql_environment('eol_statistics');
/* use to initialize 3 tables - run once */ initialize_tables(); //exit;

//=================================================================
//query 1
$query = "SELECT tcn.taxon_concept_id, n.string FROM taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id) JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) WHERE tcn.vern=0 AND tcn.preferred=1 AND tc.supercedure_id=0 AND tc.published=1 GROUP BY tcn.taxon_concept_id ORDER BY tcn.source_hierarchy_entry_id DESC "; 
$query .= " limit 2 ";
$result = $mysqli->query($query);    
$fields=array();
$fields[0]="taxon_concept_id";
$fields[1]="string";
$temp = save_to_txt($result,"hierarchies_names",$fields,$year_month,chr(9),0,"txt");
//=================================================================
//query 2
$query = "SELECT DISTINCT a.full_name, tcn.taxon_concept_id 
FROM agents a
JOIN agents_resources ar ON (a.id=ar.agent_id)
JOIN harvest_events he ON (ar.resource_id=he.resource_id)
JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id)
JOIN taxa t ON (het.taxon_id=t.id)
JOIN taxon_concept_names tcn ON (t.name_id=tcn.name_id)
WHERE a.id IN (Select agents.id From agents Inner Join content_partners ON agents.id = content_partners.agent_id 
Where content_partners.vetted = '1' Order By agents.id Asc ";
$query .= " ) ";
    
/*
WHERE a.full_name IN (
	'AmphibiaWeb', 'BioLib.cz', 'Biolib.de', 'Biopix', 'Catalogue of Life', 'FishBase',
	'Global Biodiversity Information Facility (GBIF)', 'IUCN', 'Micro*scope',
	'Solanaceae Source', 'Tree of Life web project', 'uBio','AntWeb','ARKive','Animal Diversity Web' ";
if($year >= 2009 && intval($month) > 4) $query .= " , 'The Nearctic Spider Database' ";    
*/    

$query .= " limit 2 ";

$result = $mysqli->query($query);    
$fields=array();
$fields[0]="full_name";
$fields[1]="taxon_concept_id";
$temp = save_to_txt($result,"agents_hierarchies",$fields,$year_month,chr(9),0,"txt");
//=================================================================
//query 3
$query = "SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id FROM page_names pn JOIN taxon_concept_names tcn ON (pn.name_id=tcn.name_id) ";
$query .= " LIMIT 2 ";
$result = $mysqli->query($query);    
$fields=array();
$fields[0]="full_name";
$fields[1]="taxon_concept_id";
$temp = save_to_txt($result,"agents_hierarchies_bhl",$fields,$year_month,chr(9),0,"txt");
//=================================================================
//query 4,5
$update = $mysqli2->query("TRUNCATE TABLE eol_statistics.hierarchies_names_" . $year_month . "");        
$update = $mysqli2->query("TRUNCATE TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
//query 6,7,8
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/hierarchies_names.txt' INTO TABLE eol_statistics.hierarchies_names_" . $year_month . "");        
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies.txt' INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
$update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies_bhl.txt' INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
//=================================================================
//start query9
//start query10
//start query11 - site_statistics
//start query12
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
    if($file_extension == "txt")$temp = "temp/";
    else                        $temp = "";
    
	$filename = "data/" . $year_month . "/" . $temp . "$filename" . "." . $file_extension;
	if($fp = fopen($filename,"w")){fwrite($fp,$str);fclose($fp);}		
    
}//function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)



function initialize_tables()
{
	global $mysqli2;
    global $year_month;

	$query="DROP TABLE IF EXISTS `eol_statistics`.`agents_hierarchies_" . $year_month . "`;";   $update = $mysqli2->query($query);    		
	$query="DROP TABLE IF EXISTS `eol_statistics`.`hierarchies_names_" . $year_month . "`;";    $update = $mysqli2->query($query);

	$query="CREATE TABLE  `eol_statistics`.`agents_hierarchies_" . $year_month . "` ( `agentName` varchar(64) NOT NULL, `hierarchiesID` int(10) unsigned NOT NULL, PRIMARY KEY  USING BTREE (`agentName`,`hierarchiesID`), KEY `hierarchiesID` (`hierarchiesID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8"; $update = $mysqli2->query($query);    
	$query="CREATE TABLE  `eol_statistics`.`hierarchies_names_" . $year_month . "` ( `hierarchiesID` int(10) unsigned NOT NULL, `scientificName` varchar(255) default NULL, `commonNameEN` varchar(255) default NULL, PRIMARY KEY  (`hierarchiesID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8"; $update = $mysqli2->query($query);
    
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