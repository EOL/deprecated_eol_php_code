<?php

define("ENVIRONMENT", "slave_32");
//define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$mysqli2 = load_mysql_environment('eol_statistics');        
$mysqli2 = load_mysql_environment('development'); //to be used when developing locally
        
set_time_limit(0);

/*
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html#d4Ecommerce
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceCommonCalculations.html#revenue
*/

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
    print"\n Invalid parameters  \n
    e.g. for July 2009 enter: \n
    \t php generate_tables.php 7 2009 \n\n ";
    exit();
}

$month = GetNumMonthAsString($month, $year);
$year_month = $year . "_" . $month; //$year_month = "2009_04";
$google_analytics_page_stat = "google_analytics_page_stat";


initialize_tables_4dmonth($year,$month); 
//exit(); //debug - uncomment to see if current month entries are deleted from the tables

$temp = get_from_api($month,$year); //start1
$temp = prepare_agentHierarchies_hierarchiesNames($year_month); //start2
//$temp = monthly_summary($year_month); //start3


echo"\n\n Processing done. --end-- ";    
exit;

function prepare_agentHierarchies_hierarchiesNames($year_month)
{
    global $mysqli;
    global $mysqli2;    

    //=================================================================
    //query 1 /* not needed anymore */
    //=================================================================
    //query 2    
    $query="Select distinct agents.id From agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    Where harvest_events.published_at is not null "; 
    $query .= " and agents.id = 2 "; //debug
    $query .= " order by agents.full_name ";
    //this query now only gets partners with a published data on the time the report was run.
    $query .= " limit 5 "; //debug
    $result = $mysqli->query($query);    
    
    //initialize txt file        
	$filename = "data/" . $year_month . "/temp/google_analytics_agent_page_stat.txt";    $fp = fopen($filename,"w");fclose($fp);		    
    $filename = "data/" . $year_month . "/temp/google_analytics_agent_page_stat_bhl.txt";$fp = fopen($filename,"w");fclose($fp);		    
    $filename = "data/" . $year_month . "/temp/google_analytics_agent_page_stat_col.txt";$fp = fopen($filename,"w");fclose($fp);		    	
    //initialize end
    
    echo"\n start agent stats...\n";    
    $num_rows = $result->num_rows; $i=0;
    while($result && $row=$result->fetch_assoc())	
    {
        $i++;
        echo"agent id = $row[id] $i of $num_rows \n";
        $query = "SELECT DISTINCT a.id, a.full_name, he.taxon_concept_id 
        FROM agents a 
        JOIN agents_resources ar ON (a.id=ar.agent_id) 
        JOIN harvest_events hev ON (ar.resource_id=hev.resource_id) 
        JOIN harvest_events_taxa het ON (hev.id=het.harvest_event_id) 
        JOIN taxa t ON (het.taxon_id=t.id) 
        join hierarchy_entries he on t.hierarchy_entry_id = he.id 
        join taxon_concepts tc on he.taxon_concept_id = tc.id         
        Join google_analytics_page_stat ON tc.id = google_analytics_page_stat.taxon_concept_id
        WHERE a.id = $row[id] and tc.published = 1 and tc.supercedure_id = 0 ";        
        $query .= " limit 50 "; //debug 

        $result2 = $mysqli->query($query);    
        $fields=array();
        $fields[0]="taxon_concept_id";
        $fields[1]="id";
        //$fields[1]="full_name";        
        $temp = save_to_txt($result2,"google_analytics_agent_page_stat",$fields,$year_month,chr(9),0,"txt");
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
    /* $query = "SELECT DISTINCT 'BHL' full_name, tcn.taxon_concept_id From page_names AS pn Inner Join taxon_concept_names AS tcn ON (pn.name_id = tcn.name_id) Inner Join taxon_concepts ON tcn.taxon_concept_id = taxon_concepts.id WHERE taxon_concepts.published = 1 and taxon_concepts.supercedure_id = 0 and taxon_concepts.vetted_id <> " . Vetted::find("untrusted"); */
    
    echo"\n start BHL stats...\n";    
    //before 'BHL'
    $query = "select distinct 38205 agent_id, 'Biodiversity Heritage Library' full_name, tc.id taxon_concept_id 
    from taxon_concepts tc inner JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) inner JOIN page_names pn on (tcn.name_id=pn.name_id) 
    Inner Join google_analytics_page_stat ON tc.id = google_analytics_page_stat.taxon_concept_id        
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted");
    $query .= " LIMIT 1 "; //debug
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="taxon_concept_id";
    $fields[1]="agent_id";
    //$fields[1]="full_name";    
    $temp = save_to_txt($result, "google_analytics_agent_page_stat_bhl",$fields,$year_month,chr(9),0,"txt");

    //==============================================================================================
    //start COL 2009

    /* working but don't go through taxon_concept_names
    $query = "select distinct 'COL 2009' full_name, tc.id taxon_concept_id from 
    taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "
    and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries 
    where hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().")"; */
    
    echo"\n start COL stats...\n";    
    //before 'COL 2009'
    $query = "
    select distinct 11 agent_id, 'Catalogue of Life' full_name, tc.id taxon_concept_id     
    from taxon_concepts tc inner JOIN hierarchy_entries tcn on (tc.id=tcn.taxon_concept_id) 
    Inner Join google_analytics_page_stat ON tc.id = google_analytics_page_stat.taxon_concept_id
    where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "    
    and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries 
    where hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().")";
    $query .= " LIMIT 1 "; //debug
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="taxon_concept_id";
    $fields[1]="agent_id";
    //$fields[1]="full_name";    
    $temp = save_to_txt($result, "google_analytics_agent_page_stat_col",$fields,$year_month,chr(9),0,"txt");

    //end COL 2009
    //==============================================================================================
    //=================================================================
    //query 4,5
    /* not needed anymore
    $update = $mysqli2->query("TRUNCATE TABLE eol_statistics.hierarchies_names_" . $year_month . "");        
    $update = $mysqli2->query("TRUNCATE TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    */
    //query 6,7,8
    /* changed to without year_month
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/hierarchies_names.txt'      INTO TABLE eol_statistics.hierarchies_names_" . $year_month . "");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies.txt'     INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies_bhl.txt' INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/agents_hierarchies_col.txt' INTO TABLE eol_statistics.agents_hierarchies_" . $year_month . "");        
    */
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/google_analytics_agent_page_stat.txt'     INTO TABLE google_analytics_agent_page_stat");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/google_analytics_agent_page_stat_bhl.txt' INTO TABLE google_analytics_agent_page_stat");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/temp/google_analytics_agent_page_stat_col.txt' INTO TABLE google_analytics_agent_page_stat");        
    //=================================================================

    //start query9,10,11,12 => start3.php
    //start query11 - site_statistics
    //print"<hr>xxx [$temp]<hr>"; exit($temp);
    /*
    //$query1 .= " INTO OUTFILE 'C:/webroot/eol_php_code/applications/google_stats/data/2009_07/eli.txt' FIELDS TERMINATED BY '\t' ";
    */   


}//end func //end start2


function get_sciname_from_tc_id($tc_id)
{
    global $mysqli;
    $query="Select distinct names.`string` as sciname
    From taxon_concept_names
    Inner Join taxon_concepts ON taxon_concepts.id = taxon_concept_names.taxon_concept_id
    Inner Join names ON taxon_concept_names.name_id = names.id
    Where taxon_concepts.id = $tc_id and taxon_concept_names.vern = 0
    AND taxon_concept_names.preferred=1 AND taxon_concepts.supercedure_id=0 AND taxon_concepts.published=1 limit 1 ";
    $result = $mysqli->query($query);
    $row = $result->fetch_row();            
    $sciname = $row[0];
    //print"[[$sciname -- $tc_id]]";
    return $sciname;        
}


//############################################################################ start functions


function get_from_api($month,$year)
{
    global $google_analytics_page_stat;
    global $mysqli2;
    
    $year_month = $year . "_" . $month;
    
    $start_date = "$year-$month-01";
    $end_date   = "$year-$month-" . getlastdayofmonth(intval($month), $year);           
    
    print"\n start day = $start_date \n end day = $end_date \n";
    
    
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
        //$range=100; //debug
        
        mkdir("data/" . $year . "_" . $month , 0700);        
        mkdir("data/" . $year . "_" . $month . "/temp", 0700);        
        
        $OUT = fopen("data/" . $year . "_" . $month . "/temp/" . $google_analytics_page_stat . ".txt", "w+");
        $cr = "\n";
        $sep = ",";
        $sep = chr(9); //tab
        $str = "";
        
        while($continue == true)
        {
            $data = $api->data($id, 'ga:pagePath' , 'ga:pageviews,ga:uniquePageviews,ga:bounces,ga:entrances,ga:exits,ga:timeOnPage'       
                    ,false ,$start_date ,$end_date 
                    ,$range ,$start_count ,false ,false);//96480
                    /* doesn't work with ,ga:visitors,ga:visits - these 2 work if there is no dimension, this one has a dimension 'ga:pagePath' */
            $start_count += $range;                    
            $val=array();            
            print "no. of records = " . count($data) . "\n";            
            
            if(count($data) == 0)$continue=false;        
            //$continue=false; //debug - use to force-stop loop
            
            foreach($data as $metric => $count) 
            {
                $i++; print "$i. \n";                
                // /*                
                if(true)
                {
                    //newly added start 
                    /*
                    $visits   = number_format($count["ga:visits"]);
                    $visitors = number_format($count["ga:visitors"]);
                    */
                    //newly added end
                    
                    if($count["ga:entrances"] > 0)  $bounce_rate = number_format($count["ga:bounces"]/$count["ga:entrances"]*100,2);
                    else                            $bounce_rate = "";
                    
                    if($count["ga:pageviews"] > 0)  $percent_exit = number_format($count["ga:exits"]/$count["ga:pageviews"]*100,2);
                    else                            $percent_exit = "";
                                                    
                    if($count["ga:pageviews"] - $count["ga:exits"] > 0)  
                    {
                        $secs = round($count["ga:timeOnPage"]/($count["ga:pageviews"] - $count["ga:exits"]));
                        $averate_time_on_page = $api->sec2hms($secs ,false);        
                    }
                    else $averate_time_on_page = "";
                    
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
                    
                    /* not used anymore
                    $sciname="";                    
                    if(is_numeric($taxon_id))$sciname = get_sciname_from_tc_id($taxon_id);                                        
                    //else echo" not numeric ";
                    */                    
                    
                    if($taxon_id > 0)
                    {
                        $str .= $taxon_id . $sep . 
                                intval(substr($year_month,0,4)) . $sep .
                                intval(substr($year_month,5,2)) . $sep .
                                $count["ga:pageviews"] . $sep . 
                                $count["ga:uniquePageviews"] . $sep . 
                                $averate_time_on_page . $cr;
                                /* 
                                $bounce_rate . $sep . 
                                $percent_exit . $sep . 
                                $money_index . $sep . 
                                date('Y-m-d H:i:s') . 
                                */
                    }
                }
                //print "<hr>";
                // */
                
            }//end for loop
            echo"\n More, please wait... \n";

            //exit;
                        
            fwrite($OUT, $str);
        }//end while
        fclose($OUT);
        
        
        //$update = $mysqli2->query("TRUNCATE TABLE " . $google_analytics_page_stat . "");        
        $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year . "_" . $month . "/temp/" . $google_analytics_page_stat . ".txt' INTO TABLE " . $google_analytics_page_stat . "");        
        
    }
    else 
    {
        echo "login failed \n";    
    }
    return $final;
}//function get_from_api($month,$year)

function getlastdayofmonth($month, $year) 
{
    return idate('d', mktime(0, 0, 0, ($month + 1), 0, $year));
}

function create_tables()
{   /* to be run as migrations */   
    $query="CREATE TABLE `google_analytics_agent_page_stat` (
    `taxon_concept_id` int(10) unsigned NOT NULL,
    `agent_id` int(10) unsigned NOT NULL,
    `year` smallint(4) NOT NULL,
    `month` tinyint(2) NOT NULL,
    KEY `taxon_concept_id` (`taxon_concept_id`),
    KEY `agent_id` (`agent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $update = $mysqli->query($query);    

    $query="CREATE TABLE `google_analytics_page_stat` (
    `taxon_concept_id` int(10) unsigned NOT NULL default '0',
    `year` smallint(4) NOT NULL,
    `month` tinyint(2) NOT NULL,
    `page_views` int(10) unsigned NOT NULL,
    `unique_page_views` int(10) unsigned NOT NULL,
    `time_on_page` time NOT NULL,
    PRIMARY KEY  (`taxon_concept_id`),
    KEY `taxon_concept_id` (`taxon_concept_id`),
    KEY `page_views` (`page_views`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $update = $mysqli->query($query);    

    /*
	$query="CREATE TABLE  `google_analytics_agent_page_stat` ( 
    `agent_id` int(10) unsigned NOT NULL,
    `agentName` varchar(64) NOT NULL,
    `taxon_concept_id` int(10) unsigned NOT NULL,
    `year_month` varchar(8) NOT NULL default '',
    PRIMARY KEY  (`agent_id`,`taxon_concept_id`,`year_month`),
    KEY `taxon_concept_id` (`taxon_concept_id`),
    KEY `agent_id` (`agent_id`),
    KEY `year_month` (`year_month`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ";     
    $update = $mysqli->query($query);    

	$query="CREATE TABLE `google_analytics_page_stat` ( 
    `id` int(10) unsigned NOT NULL auto_increment,
    `taxon_concept_id` int(10) unsigned default NULL,
    `year_month` varchar(8) default NULL,
    `sciname` varchar(100) default NULL,
    `url` varchar(1000) NOT NULL,
    `page_views` int(10) unsigned NOT NULL,
    `unique_page_views` int(10) unsigned NOT NULL,
    `time_on_page` time NOT NULL,
    `bounce_rate` float default NULL,
    `percent_exit` float default NULL,
    `money_index` float default NULL,
    `date_added` datetime NOT NULL,
    PRIMARY KEY  (`id`),
    KEY `year_month` (`year_month`),
    KEY `taxon_concept_id` (`taxon_concept_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ";
	$update = $mysqli->query($query);    
    //`visits` int(10) unsigned NOT NULL, 
    //`visitors` int(10) unsigned NOT NULL,     
    */

}

function initialize_tables_4dmonth($year,$month)
{	global $mysqli2;    
    //$month=intval($month);
    $query="delete from `google_analytics_page_stat`       where `year` = $year and `month` = $month ";  $update = $mysqli2->query($query);        
    $query="delete from `google_analytics_agent_page_stat` where `year` = $year and `month` = $month ";  $update = $mysqli2->query($query);    		    
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
			$str .= $row["$field"] . $field_separator;    //chr(9) is tab
		}
        $str .= intval(substr($year_month,0,4)) . $field_separator;
        $str .= intval(substr($year_month,5,2)) . $field_separator;
		$str .= "\n";
	}
    if($file_extension == "txt")$temp = "temp/";
    else                        $temp = "";
    
	$filename = "data/" . $year_month . "/" . $temp . "$filename" . "." . $file_extension;
	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		
    
    //print "\n[$i]\n";
    
    return "";
    
}//function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)
?>