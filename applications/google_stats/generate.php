<?php

//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];
//exit;


$mysqli2 = load_mysql_environment('eol_statistics');        
set_time_limit(0);

/*
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html#d4Ecommerce
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceCommonCalculations.html#revenue
*/

$arr = process_parameters();//month and year parameters
$month = $arr[0]; $year = $arr[1]; $year_month = $year . "_" . $month; //$year_month = "2009_04";


//exit("finish test");

$month = GetNumMonthAsString($month, $year);

$year_month = $year . "_" . $month;
//$year_month = "2009_04";
$google_analytics_page_statistics = "google_analytics_page_statistics_" . $year . "_" . $month;

// /* //start1
initialize_tables_4dmonth();
$api = get_from_api($month,$year);    
//exit("<hr>finished start1 only");
//end
// */

// /*
$temp = prepare_agentHierarchies_hierarchiesNames($year_month); //start start2
// */

$temp = create_csv_files($year_month);                          //start start3

function create_csv_files($year_month)
{
    global $mysqli2;
    global $google_analytics_page_statistics;
    //=================================================================
    //query 1
    //query 2
    //query 3
    //query 4,5
    //query 6,7,8
        //=================================================================
    //start query9
    $query="SELECT (SELECT COUNT(*) FROM eol_statistics.hierarchies_names_" . $year_month . ") all_taxa_count, 
    agentName, COUNT(*) agent_taxa_count FROM eol_statistics.agents_hierarchies_" . $year_month . " 
    GROUP BY agent_id ORDER BY agentName;
    ";
    //GROUP BY agentName ORDER BY agentName;
    $result = $mysqli2->query($query);    
    $fields=array();
    $fields[0]="all_taxa_count";
    $fields[1]="agentName";
    $fields[2]="agent_taxa_count";
    $temp = save_to_txt($result,"query9",$fields,$year_month,",",1,"csv");
    //end query9
    //=================================================================
    //start query10
    $query="SELECT g.id, g.date_added, g.taxon_id, g.url, hn.scientificName, hn.commonNameEN, g.page_views, g.unique_page_views, TIME_TO_SEC(g.time_on_page) time_on_page_seconds, g.bounce_rate, g.percent_exit FROM eol_statistics." . $google_analytics_page_statistics . " g LEFT OUTER JOIN eol_statistics.hierarchies_names_" . $year_month . " hn ON hn.hierarchiesID = g.taxon_id ";
    //$query .= " WHERE g.date_added > ADDDATE(CURDATE(), -1) ";
    $query .= " ORDER BY page_views DESC, unique_page_views DESC, time_on_page_seconds DESC; ";
    $result = $mysqli2->query($query);    
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
    $query="SELECT ah.agentName,g.taxon_id, hn.scientificName, hn.commonNameEN,SUM(g.page_views) total_page_views,SUM(g.unique_page_views) total_unique_page_views,SUM(TIME_TO_SEC(g.time_on_page)) total_time_on_page_seconds FROM eol_statistics." . $google_analytics_page_statistics . " g INNER JOIN eol_statistics.agents_hierarchies_" . $year_month . " ah	ON ah.hierarchiesID = g.taxon_id LEFT OUTER JOIN eol_statistics.hierarchies_names_" . $year_month . " hn ON hn.hierarchiesID=g.taxon_id ";
    //$query .= " WHERE g.date_added > ADDDATE(CURDATE(), -1) ";
    $query .= " GROUP BY ah.agent_id, g.taxon_id ORDER BY ah.agentName, total_page_views DESC, total_unique_page_views DESC, total_time_on_page_seconds DESC ";
    //GROUP BY ah.agentName,
    $result = $mysqli2->query($query);    
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
    $query="SELECT distinct g.taxon_id FROM eol_statistics." . $google_analytics_page_statistics . " g 
    WHERE g.taxon_id>0 ";
    //$query .= " and g.date_added > ADDDATE(CURDATE(), -1) ";
    
    $result = $mysqli2->query($query);    
    $fields=array();
    $fields[]="taxon_id";
    $temp = save_to_txt($result,"query12",$fields,$year_month,",",1,"csv");
    //end query12
    //=================================================================
    
}//create_csv_files



function prepare_agentHierarchies_hierarchiesNames($year_month)
{
    global $mysqli;
    global $mysqli2;
    
    initialize_tables(); //exit;

    //=================================================================
    //query 1
    $query = "SELECT tcn.taxon_concept_id, n.string FROM taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id) 
    JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) 
    WHERE tcn.vern=0 AND tcn.preferred=1 AND tc.supercedure_id=0 AND tc.published=1 GROUP BY tcn.taxon_concept_id 
    ORDER BY tcn.source_hierarchy_entry_id DESC "; 
    //$query .= " limit 1 "; //debug ??? maybe can't be limited, even on when debugging
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="taxon_concept_id";
    $fields[1]="string";
    $temp = save_to_txt($result,"hierarchies_names",$fields,$year_month,chr(9),0,"txt");
    //=================================================================
    //query 2
    
    /*
    $query="Select agents.id From agents Inner Join content_partners ON agents.id = content_partners.agent_id 
    Where content_partners.eol_notified_of_acceptance Is Not Null
    Order By agents.full_name Asc "; */ 
    $query="Select distinct agents.id From agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    Where harvest_events.published_at is not null order by agents.full_name "; 
    //this query now only gets partners with a published data on the time the report was run.

    //$query .= " limit 1 "; //debug
    $result = $mysqli->query($query);    

    while($result && $row=$result->fetch_assoc())	
    {
        /* legacy version
        $query = "SELECT DISTINCT a.full_name, tcn.taxon_concept_id 
        FROM agents a
        JOIN agents_resources ar ON (a.id=ar.agent_id)
        JOIN harvest_events he ON (ar.resource_id=he.resource_id)
        JOIN harvest_events_taxa het ON (he.id=het.harvest_event_id)
        JOIN taxa t ON (het.taxon_id=t.id)
        JOIN taxon_concept_names tcn ON (t.name_id=tcn.name_id)
        WHERE a.id = $row[id] ";
        */
        /* new Sep21, as suggested by PL */
        $query = "SELECT DISTINCT a.id, a.full_name, he.taxon_concept_id 
        FROM agents a
        JOIN agents_resources ar ON (a.id=ar.agent_id)
        JOIN harvest_events hev ON (ar.resource_id=hev.resource_id)
        JOIN harvest_events_taxa het ON (hev.id=het.harvest_event_id)
        JOIN taxa t ON (het.taxon_id=t.id)
        join hierarchy_entries he on t.hierarchy_entry_id = he.id
        join taxon_concepts tc on he.taxon_concept_id = tc.id
        WHERE a.id = $row[id] and tc.published = 1 and tc.supercedure_id = 0 ";    
    
        //$query .= " limit 100 "; //debug 

        $result2 = $mysqli->query($query);    
        $fields=array();
        $fields[0]="id";
        $fields[1]="full_name";
        $fields[2]="taxon_concept_id";
        $temp = save_to_txt($result2,"agents_hierarchies",$fields,$year_month,chr(9),0,"txt");
    }
    
    //exit("<hr>stopx");
    
    /*
    WHERE a.full_name IN (
    	'AmphibiaWeb', 'BioLib.cz', 'Biolib.de', 'Biopix', 'Catalogue of Life', 'FishBase',
    	'Global Biodiversity Information Facility (GBIF)', 'IUCN', 'Micro*scope',
    	'Solanaceae Source', 'Tree of Life web project', 'uBio','AntWeb','ARKive','Animal Diversity Web' ";
    if($year >= 2009 && intval($month) > 4) $query .= " , 'The Nearctic Spider Database' ";    
    */    




    //=================================================================
    //query 3
    /*legacy
    $query = "SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id FROM page_names pn JOIN taxon_concept_names tcn ON (pn.name_id=tcn.name_id)";
    */
    //either of these 2 queries will work
    /* $query = "SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id From page_names AS pn Inner Join taxon_concept_names AS tcn ON (pn.name_id = tcn.name_id) Inner Join taxon_concepts ON tcn.taxon_concept_id = taxon_concepts.id WHERE taxon_concepts.published = 1 and taxon_concepts.supercedure_id = 0 and taxon_concepts.vetted_id <> " . Vetted::find("untrusted") . " "; */

    //before 'BHL'
    $query = "select distinct 38205 agent_id, 'Biodiversity Heritage Library' full_name, tc.id taxon_concept_id from taxon_concepts tc 
    STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
    STRAIGHT_JOIN page_names pn on (tcn.name_id=pn.name_id) 
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted");
    //$query .= " LIMIT 1 "; //debug
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="agent_id";
    $fields[1]="full_name";
    $fields[2]="taxon_concept_id";
    $temp = save_to_txt($result, "agents_hierarchies_bhl",$fields,$year_month,chr(9),0,"txt");

    //==============================================================================================
    //start COL 2009

    /* working but don't go through taxon_concept_names
    $query = "select distinct 'COL 2009' full_name, tc.id taxon_concept_id from 
    taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "   
    and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries where 
    hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().")"; */

    //before 'COL 2009'
    $query = "select distinct 11 agent_id, 'Catalogue of Life' full_name, tc.id taxon_concept_id from 
    taxon_concepts tc STRAIGHT_JOIN hierarchy_entries tcn on (tc.id=tcn.taxon_concept_id) 
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "    
    and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries 
    where hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().")";

    //$query .= " LIMIT 1 "; //debug
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="agent_id";
    $fields[1]="full_name";
    $fields[2]="taxon_concept_id";
    $temp = save_to_txt($result, "agents_hierarchies_col",$fields,$year_month,chr(9),0,"txt");

    //end COL 2009
    //==============================================================================================


    //=================================================================
    //query 4,5
    $update = $mysqli2->query("TRUNCATE TABLE eol_statistics.hierarchies_names_" . $year_month . "");        
    $update = $mysqli2->query("TRUNCATE TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    //query 6,7,8
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/hierarchies_names.txt'      INTO TABLE eol_statistics.hierarchies_names_" . $year_month . "");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies.txt'     INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies_bhl.txt' INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies_col.txt' INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    //=================================================================

    //start query9,10,11,12 => start3.php
    //start query11 - site_statistics
    //print"<hr>xxx [$temp]<hr>"; exit($temp);
    /*
    //$query1 .= " INTO OUTFILE 'C:/webroot/eol_php_code/applications/google_stats/data/2009_07/eli.txt' FIELDS TERMINATED BY '\t' ";
    */   


}//end func


//############################################################################ start functions


function get_from_api($month,$year)
{
    global $google_analytics_page_statistics;
    global $mysqli2;
    
    $start_date = "$year-$month-01";
    $end_date   = "$year-$month-" . getlastdayofmonth(intval($month), $year);           
    
    print"<hr>
    start = $start_date <br>
    end = $end_date   <hr>    
    ";
    
    $final = array();
    
    require_once(LOCAL_ROOT . '/classes/modules/Google_Analytics_API_PHP/analytics_api.php');
    
    $login = GOOGLE_ANALYTICS_API_USERNAME;
    $password = GOOGLE_ANALYTICS_API_PASSWORD;
    $id = '';
    
    $api = new analytics_api();
    if($api->login($login, $password)) 
    {
        //echo "login success <br>";
        if(true) 
        {
            $api->load_accounts();
            $arr=$api->accounts;
        }
        $id=$arr["www.eol.org"]["tableId"];
    
        //print"<hr><hr>";
        // get some account summary information without a dimension
        $i=0;
        $continue=true; 
        $start_count=1; 
        //$start_count=30001;
        $range=10000;
        $range=10000;
        
        mkdir("data/" . $year . "_" . $month , 0777);        
        mkdir("data/" . $year . "_" . $month . "/temp", 0777);        
        
        $OUT = fopen("data/" . $year . "_" . $month . "/temp/" . $google_analytics_page_statistics . ".txt", "w+");
        $cr = "\n";
        $sep = ",";
        $sep = chr(9); //tab
                
        while($continue == true)
        {
            $data = $api->data($id, 'ga:pagePath' , 'ga:pageviews,ga:uniquePageviews,ga:bounces,ga:entrances,ga:exits,ga:timeOnPage'       
                    ,false ,$start_date ,$end_date 
                    ,$range ,$start_count ,false ,false);//96480
            $start_count += $range;                    
            $val=array();            
            print "no. of records = " . count($data) . "<br>";            
            
            if(count($data) == 0)$continue=false;        
            /* for debugging */ //$continue=false;
        
            $str = "";    
            foreach($data as $metric => $count) 
            {
                $i++; print "$i. ";                
                // /*                
                if(true)
                {
                    if($count["ga:entrances"] > 0)  $bounce_rate = number_format($count["ga:bounces"]/$count["ga:entrances"]*100,2);
                    else                            $bounce_rate = "";
                    
                    if($count["ga:pageviews"] > 0)  $percent_exit = number_format($count["ga:exits"]/$count["ga:pageviews"]*100,2);
                    else                            $percent_exit = "";
                                                    
                    if($count["ga:pageviews"] - $count["ga:exits"] > 0)  
                    {
                        $secs = round($count["ga:timeOnPage"]/($count["ga:pageviews"] - $count["ga:exits"]));
                        $averate_time_on_page = $api->sec2hms($secs ,false);        
                    }
                    else                                                  $averate_time_on_page = "";
                    
                    /*
                    echo " -- " . $bounce_rate;
                    echo " -- " . $percent_exit;
                    echo " -- " . $averate_time_on_page;                                    
                    print " | ga:entrances = " . $count["ga:entrances"];
                    print " | pageviews = " . $count["ga:pageviews"] ;
                    print " | uniquePageviews = " . $count["ga:uniquePageviews"] ;
                    print " | exits = " . $count["ga:exits"];
                    print " | url = " . $metric;
                    */
                    
                    $money_index = '';
                    
                    //print " | count = " . count($count) . "";
                    $url = "http://www.eol.org" . $metric;
                    $taxon_id = parse_url($url, PHP_URL_PATH);
                    //print "[$taxon_id]";
                    if(strval(stripos($taxon_id,"/pages/"))!= '')$taxon_id = str_ireplace("/pages/", "", $taxon_id);
                    else                                         $taxon_id = '';
                    //print "[$taxon_id]";
                    
                    $str .= $i . $sep . $taxon_id . $sep . $url . $sep . $count["ga:pageviews"] . $sep . $count["ga:uniquePageviews"] . $sep . 
                            $averate_time_on_page . $sep . 
                            $bounce_rate . $sep . $percent_exit . $sep . $money_index . $sep . date('Y-m-d H:i:s') 
                            . $cr;
                }
                //print "<hr>";
                // */
                
            }//end for loop

            //exit;
                        
            fwrite($OUT, $str); // transferred out of the while
        }//end while
        fclose($OUT);        
        
        $update = $mysqli2->query("TRUNCATE TABLE eol_statistics." . $google_analytics_page_statistics . "");        
        $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year . "_" . $month . "/temp/" . $google_analytics_page_statistics . ".txt' INTO TABLE eol_statistics." . $google_analytics_page_statistics . "");        
        
    }
    else 
    {
        echo "login failed <br>";    
    }
    return $final;
}//function get_from_api($month,$year)

function getlastdayofmonth($month, $year) 
{
    return idate('d', mktime(0, 0, 0, ($month + 1), 0, $year));
}

function initialize_tables_4dmonth()
{
	global $mysqli2;
    global $google_analytics_page_statistics;
    
    $query="DROP TABLE IF EXISTS `eol_statistics`.`" . $google_analytics_page_statistics . "`;"; 
    $update = $mysqli2->query($query);
	$query="CREATE TABLE  `eol_statistics`.`" . $google_analytics_page_statistics . "` ( `id` int(10) unsigned NOT NULL auto_increment, `taxon_id` int(10) unsigned default NULL, `url` varchar(1000) NOT NULL, 
    `page_views` int(10) unsigned NOT NULL, 
    `unique_page_views` int(10) unsigned NOT NULL, 
    `time_on_page` time NOT NULL, 
    `bounce_rate` float default NULL, `percent_exit` float default NULL, `money_index` float default NULL, `date_added` datetime NOT NULL, PRIMARY KEY  (`id`) ) ENGINE=InnoDB AUTO_INCREMENT=240640 DEFAULT CHARSET=utf8";
	$update = $mysqli2->query($query);

}//function initialize_tables_4dmonth()
function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}

function GetNumMonthAsString($m,$y)
{
    $timestamp = mktime(0, 0, 0, $m, 1, $y);    
    return date("m", $timestamp);
}

//#############################################################################################################
/* functions of start2 */

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
            
            $row_field="";
            if($file_extension == "csv") $row_field = str_ireplace(",", "&#44;", $row["$field"]);
            else                         $row_field = $row["$field"];                                    
			$str .= $row_field . $field_separator;    //chr(9) is tab
            
		}
		$str .= "\n";
	}
    if($file_extension == "txt")$temp = "temp/";
    else                        $temp = "";
    
	$filename = "data/" . $year_month . "/" . $temp . "$filename" . "." . $file_extension;
	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		
    
    //print "<br>[$i]<br>";
    
    return "";
    
}//function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)

function initialize_tables()
{
	global $mysqli2;
    global $year_month;

	$query="DROP TABLE IF EXISTS `eol_statistics`.`agents_hierarchies_" . $year_month . "`;";   $update = $mysqli2->query($query);    		
	$query="DROP TABLE IF EXISTS `eol_statistics`.`hierarchies_names_" . $year_month . "`;";    $update = $mysqli2->query($query);

	$query="CREATE TABLE  `eol_statistics`.`agents_hierarchies_" . $year_month . "` ( `agent_id` int(10) unsigned NOT NULL, `agentName` varchar(64) NOT NULL, `hierarchiesID` int(10) unsigned NOT NULL, PRIMARY KEY  USING BTREE (`agent_id`,`hierarchiesID`), KEY `hierarchiesID` (`hierarchiesID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8"; $update = $mysqli2->query($query);    
	$query="CREATE TABLE  `eol_statistics`.`hierarchies_names_" . $year_month . "` ( `hierarchiesID` int(10) unsigned NOT NULL, `scientificName` varchar(255) default NULL, `commonNameEN` varchar(255) default NULL, PRIMARY KEY  (`hierarchiesID`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8"; $update = $mysqli2->query($query);
    
}//function initialize_tables()
function process_parameters()
{
    global $argv;

    $month = get_val_var("month");
    $year = get_val_var("year");
    
    if($month == "")
    {
        $arg1='';   
        $arg2='';
        if(isset($argv[0])) $arg0=$argv[0]; //this is the filename xxx.php
        if(isset($argv[1])) $month=$argv[1];        
        if(isset($argv[2])) $year=$argv[2];        
        print"
        month = $month  \n
        year = $year    \n
        ";    
        if($month != "" and $year != "") print"Processing, please wait...  \n\n ";
    }
    if($month == "" or $year == "" or $year < 2008 or $year > date('Y') or $month < 1 or $month > 12)
    {
        print"\n Invalid parameters!\n
        e.g. for July 2009 enter: \n
        \t php generate_monthly_stats.php 7 2009 \n\n ";
        exit();
    }
    $month = GetNumMonthAsString($month, $year);
    $arr = array();
    $arr[]=$month;
    $arr[]=$year;
    return $arr;
}//function process_parameters()




?>