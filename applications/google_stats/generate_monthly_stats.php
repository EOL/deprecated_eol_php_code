<?php
//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
require_once('google_proc.php');
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

$arr = process_parameters();//month and year parameters
$month = $arr[0];
$year = $arr[1];
$year_month = $year . "_" . $month; //$year_month = "2009_04";

// /*
initialize_tables_4dmonth($year,$month); 
//exit(); //debug - uncomment to see if current month entries are deleted from the tables

$temp = save_eol_taxa_google_stats($month,$year); //start1 //exit("<hr>finished start1 only");
$temp = save_agent_taxa($year_month); //start2
$temp = save_agent_monthly_summary($year_month);                           //
// */
$temp = save_eol_monthly_summary($year,$month);

echo"\n\n Processing done. --end-- "; exit;

//####################################################################################################################################
//####################################################################################################################################
function process_parameters()
{
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

function save_to_txt2($arr,$filename,$year_month,$field_separator,$file_extension)
{
	$str="";        
	for ($i = 0; $i < count($arr); $i++) 		
	{
		$field = $arr[$i];
		$str .= $field . $field_separator;    //chr(9) is tab
	}
	$str .= "\n";
  
	$filename = "data/" . $year_month . "/" . "$filename" . "." . $file_extension;
	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		
    
    return "";
    
}//function save_to_txt2

function get_monthly_summaries_per_partner($agent_id,$year,$month,$count_of_taxa_pages,$count_of_taxa_pages_viewed)
{
    global $mysqli2;
    //start get count_of_taxa_pages viewed during the month, etc.
    $query = "Select distinct
     Sum(google_analytics_page_stat.page_views) AS page_views,
    Sum(google_analytics_page_stat.unique_page_views) AS unique_page_views,
    Sum(time_to_sec(google_analytics_page_stat.time_on_page)) /60/60 AS time_on_page
    From google_analytics_partner_taxa
    Inner Join google_analytics_page_stat ON google_analytics_partner_taxa.taxon_concept_id = google_analytics_page_stat.taxon_concept_id AND google_analytics_partner_taxa.`year` = google_analytics_page_stat.`year` AND google_analytics_partner_taxa.`month` = google_analytics_page_stat.`month`
    Where
    google_analytics_partner_taxa.agent_id = $agent_id AND
    google_analytics_partner_taxa.`year` = $year AND
    google_analytics_partner_taxa.`month` = $month ";        
    $result2 = $mysqli2->query($query);    
    $row2 = $result2->fetch_row();            
        
    $page_views         = $row2[0];
    $unique_page_views  = $row2[1];
    $time_on_page       = $row2[2];        
        
    $arr=array();
    $arr[]=$year;
    $arr[]=$month;
    $arr[]=$agent_id;    
    $arr[]=$count_of_taxa_pages;
    $arr[]=$count_of_taxa_pages_viewed;
    $arr[]=$unique_page_views;
    $arr[]=$page_views;
    $arr[]=$time_on_page;
    //end get count_of_taxa_pages viewed during the month, etc.        
    return $arr;
}

function get_count_of_taxa_pages_per_partner($agent_id,$year,$month)
{
    global $mysqli;
    $arr=array();
    
    if($agent_id == 38205)//BHL
    {           
        $query = "Select distinct tc.id
        from taxon_concepts tc 
        inner JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
        inner JOIN page_names pn on (tcn.name_id=pn.name_id)
        where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted");                 
    }
    elseif($agent_id == 11)//Catalogue of Life
    {   $query = "Select distinct Count(tc.id)     
        from taxon_concepts tc 
        inner JOIN hierarchy_entries tcn on (tc.id=tcn.taxon_concept_id) 
        where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "    
        and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries 
        where hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().")";
    }
    else //rest of the partners
    {   $query = "Select distinct Count(he.taxon_concept_id) 
        FROM agents a 
        JOIN agents_resources ar ON (a.id=ar.agent_id) 
        JOIN harvest_events hev ON (ar.resource_id=hev.resource_id) 
        JOIN harvest_events_taxa het ON (hev.id=het.harvest_event_id) 
        JOIN taxa t ON (het.taxon_id=t.id) 
        join hierarchy_entries he on t.hierarchy_entry_id = he.id 
        join taxon_concepts tc on he.taxon_concept_id = tc.id 
        WHERE a.id = $agent_id and tc.published = 1 and tc.supercedure_id = 0 ";                
    }
    $result2 = $mysqli->query($query);            
    $row2 = $result2->fetch_row();                
    
    if($agent_id == 38205)  $arr[] = $result2->num_rows; //count of taxa pages
    else                    $arr[] = $row2[0]; //count of taxa pages

    //ditox dapat my inner join sa goolge_page_stats table
    $query="Select Count(google_analytics_partner_taxa.taxon_concept_id)
    From google_analytics_partner_taxa Where
    google_analytics_partner_taxa.agent_id = $agent_id AND
    google_analytics_partner_taxa.`year` = $year AND
    google_analytics_partner_taxa.`month` = $month ";
    $result2 = $mysqli->query($query);            
    $row2 = $result2->fetch_row();                
    $arr[] = $row2[0]; //count of taxa pages viewed during the month

    return $arr;
}

function get_sql_for_partners_with_published_data()
{
    //this query now only gets partners with a published data on the time the report was run.
    $query="Select distinct agents.id From agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    Where harvest_events.published_at is not null "; 
    //$query .= " and agents.id = 2 "; //debug FishBase
    $query .= " order by agents.full_name ";    
    //$query .= " limit 5 "; //debug
    return $query;
}

function save_agent_monthly_summary($year_month)
{
    global $mysqli;
    global $mysqli2;    

    $year =intval(substr($year_month,0,4));
    $month=intval(substr($year_month,5,2));
    
    //=================================================================
    $query = get_sql_for_partners_with_published_data();
    $result = $mysqli->query($query);    
    
    //initialize txt file        
	$filename = "data/" . $year_month . "/google_analytics_partner_summaries.txt";    $fp = fopen($filename,"w");fclose($fp);		    
    
    echo"\n start agent stat summaries...\n";    
    $num_rows = $result->num_rows; $i=0;
    while($result && $row=$result->fetch_assoc())	
    {
        $i++;
        echo"agent id = $row[id] $i of $num_rows \n";        
        $arr = get_count_of_taxa_pages_per_partner($row["id"],$year,$month);
            $count_of_taxa_pages = $arr[0];
            $count_of_taxa_pages_viewed = $arr[1];        
        $arr = get_monthly_summaries_per_partner($row["id"],$year,$month,$count_of_taxa_pages,$count_of_taxa_pages_viewed);
        $temp = save_to_txt2($arr, "google_analytics_partner_summaries",$year_month,chr(9),"txt");                        
    }//end while
    //=================================================================    
    echo"\n start BHL stats summaries...\n";    
    $arr = get_count_of_taxa_pages_per_partner(38205,$year,$month);
        $count_of_taxa_pages = $arr[0];
        $count_of_taxa_pages_viewed = $arr[1];    
    $arr = get_monthly_summaries_per_partner(38205,$year,$month,$count_of_taxa_pages,$count_of_taxa_pages_viewed);
    $temp = save_to_txt2($arr, "google_analytics_partner_summaries",$year_month,chr(9),"txt");                
    
    echo"\n start COL stats summaries...\n";    
    $arr = get_count_of_taxa_pages_per_partner(11,$year,$month);
        $count_of_taxa_pages = $arr[0];
        $count_of_taxa_pages_viewed = $arr[1];
    $arr = get_monthly_summaries_per_partner(11,$year,$month,$count_of_taxa_pages,$count_of_taxa_pages_viewed);
    $temp = save_to_txt2($arr, "google_analytics_partner_summaries",$year_month,chr(9),"txt");                
    //=================================================================
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/google_analytics_partner_summaries.txt' 
    INTO TABLE google_analytics_partner_summaries");        
    //=================================================================

}//end func //end start3


function get_sql_to_get_TCid_that_where_viewed_for_dmonth($agent_id,$month,$year)
{
    if($agent_id == 38205)//BHL
    {   
        /* working but don't go through taxon_concept_names
        $query = "select distinct 'COL 2009' full_name, tc.id taxon_concept_id from 
        taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
        where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "
        and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries 
        where hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().")"; */
            
        $query = "select distinct 38205 agent_id, 'Biodiversity Heritage Library' full_name, tc.id taxon_concept_id 
        from taxon_concepts tc inner JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) 
        inner JOIN page_names pn on (tcn.name_id=pn.name_id) 
        Inner Join google_analytics_page_stat gaps ON tc.id = gaps.taxon_concept_id        
        where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . " 
        and gaps.month=$month and gaps.year=$year ";
        //$query .= " LIMIT 1 "; //debug
    }
    elseif($agent_id == 11)//Catalogue of Life
    {   
        $query = "
        select distinct 11 agent_id, 'Catalogue of Life' full_name, tc.id taxon_concept_id     
        from taxon_concepts tc inner JOIN hierarchy_entries tcn on (tc.id=tcn.taxon_concept_id) 
        Inner Join google_analytics_page_stat gaps ON tc.id = gaps.taxon_concept_id
        where tc.supercedure_id=0 and tc.published=1 and tc.vetted_id <> " . Vetted::find("untrusted") . "    
        and tcn.name_id in (Select distinct hierarchy_entries.name_id From hierarchy_entries 
        where hierarchy_entries.hierarchy_id = ".Hierarchy::col_2009().") 
        and gaps.month=$month and gaps.year=$year ";
        //$query .= " LIMIT 1 "; //debug    
    }
    else //rest of the partners
    {   
        $query = "SELECT DISTINCT a.id, he.taxon_concept_id 
        FROM agents a 
        JOIN agents_resources ar ON (a.id=ar.agent_id) 
        JOIN harvest_events hev ON (ar.resource_id=hev.resource_id) 
        JOIN harvest_events_taxa het ON (hev.id=het.harvest_event_id) 
        JOIN taxa t ON (het.taxon_id=t.id) 
        join hierarchy_entries he on t.hierarchy_entry_id = he.id 
        join taxon_concepts tc on he.taxon_concept_id = tc.id         
        Join google_analytics_page_stat gaps ON tc.id = gaps.taxon_concept_id
        WHERE a.id = $agent_id and tc.published = 1 and tc.supercedure_id = 0
        and gaps.month=$month and gaps.year=$year
        ";        
        //$query .= " limit 50 "; //debug     
    }
    return $query;
}


function save_agent_taxa($year_month)
{
    global $mysqli;
    global $mysqli2;    

    $year =intval(substr($year_month,0,4));
    $month=intval(substr($year_month,5,2));    
    
    //=================================================================
    //query 1 /* not needed anymore */
    //=================================================================
    //query 2        
    $query = get_sql_for_partners_with_published_data();
    $result = $mysqli->query($query);    
    
    //initialize txt file        
	$filename = "data/" . $year_month . "/google_analytics_partner_taxa.txt";    $fp = fopen($filename,"w");fclose($fp);		    
    $filename = "data/" . $year_month . "/google_analytics_partner_taxa_bhl.txt";$fp = fopen($filename,"w");fclose($fp);		    
    $filename = "data/" . $year_month . "/google_analytics_partner_taxa_col.txt";$fp = fopen($filename,"w");fclose($fp);		    	
    //initialize end
    
    echo"\n start agent stats...\n";    
    $num_rows = $result->num_rows; $i=0;
    while($result && $row=$result->fetch_assoc())	
    {
        $i++;
        echo"agent id = $row[id] $i of $num_rows \n";
        $query = get_sql_to_get_TCid_that_where_viewed_for_dmonth($row["id"],$month,$year);
        $result2 = $mysqli->query($query);    
        $fields=array();
        $fields[0]="taxon_concept_id";
        $fields[1]="id";
        $temp = save_to_txt($result2,"google_analytics_partner_taxa",$fields,$year_month,chr(9),0,"txt");
    }
    

    //=================================================================
    //query 3
    
    echo"\n start BHL stats...\n";    
    $query = get_sql_to_get_TCid_that_where_viewed_for_dmonth(38205,$month,$year);
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="taxon_concept_id";
    $fields[1]="agent_id";
    $temp = save_to_txt($result, "google_analytics_partner_taxa_bhl",$fields,$year_month,chr(9),0,"txt");
    
    echo"\n start COL stats...\n";    
    $query = get_sql_to_get_TCid_that_where_viewed_for_dmonth(11,$month,$year);
    $result = $mysqli->query($query);    
    $fields=array();
    $fields[0]="taxon_concept_id";
    $fields[1]="agent_id";
    $temp = save_to_txt($result, "google_analytics_partner_taxa_col",$fields,$year_month,chr(9),0,"txt");

    //=================================================================
    //query 4,5 /* not needed anymore */
    //query 6,7,8
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/google_analytics_partner_taxa.txt'     INTO TABLE google_analytics_partner_taxa");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/google_analytics_partner_taxa_bhl.txt' INTO TABLE google_analytics_partner_taxa");        
    $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year_month . "/google_analytics_partner_taxa_col.txt' INTO TABLE google_analytics_partner_taxa");        
    //=================================================================

    //start query9,10,11,12 => start3.php
    //start query11 - site_statistics
    //print"<hr>xxx [$temp]<hr>"; exit($temp);
    /*
    //$query1 .= " INTO OUTFILE 'C:/webroot/eol_php_code/applications/google_stats/data/2009_07/eli.txt' FIELDS TERMINATED BY '\t' ";
    */   

}//end func //end start2

/*working commented
function get_sciname_from_tc_id($tc_id)
{   global $mysqli;
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
}*/
//############################################################################ start functions

function save_eol_taxa_google_stats($month,$year)
{
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
        $range=10000; //debug
        
        mkdir("data/" . $year . "_" . $month , 0700);        
        
        $OUT = fopen("data/" . $year . "_" . $month . "/google_analytics_page_stat.txt", "w+");
        $cr = "\n";
        $sep = ",";
        $sep = chr(9); //tab
                
        $cnt = 0;
        while($continue == true)
        {
            $data = $api->data($id, 'ga:pagePath' , 'ga:pageviews,ga:uniquePageviews,ga:bounces,ga:entrances,ga:exits,ga:timeOnPage'       
                    ,false ,$start_date ,$end_date 
                    ,$range ,$start_count ,false ,false);//96480
                    /* doesn't work with ,ga:visitors,ga:visits - these 2 work if there is no dimension, this one has a dimension 'ga:pagePath' */
            $start_count += $range;                    
            $val=array();            
            print "Process batch of = " . count($data) . "\n";            
         
            $cnt++;   
            if(count($data) == 0)$continue=false;        
            //if($cnt == 1)$continue=false; //debug - use to force-stop loop     
        
            $str = "";    
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
                                $averate_time_on_page 
                                . $cr;
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
                        
            fwrite($OUT, $str); // transferred out of the while
        }//end while
        fclose($OUT);        

        print"ditox";        
        $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year . "_" . $month . "/google_analytics_page_stat.txt' INTO TABLE google_analytics_page_stat");      
        
    }
    else 
    {
        echo "login failed \n";    
    }
    return $final;
}//function get_from_api($month,$year)


function create_tables()
{   /* to be run as migrations */   
    $query="CREATE TABLE `google_analytics_partner_taxa` (
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

}

function initialize_tables_4dmonth($year,$month)
{	global $mysqli2;    
    //$month=intval($month);
    $query="delete from `google_analytics_page_stat`         where `year` = $year and `month` = $month ";  $update = $mysqli2->query($query);        
    $query="delete from `google_analytics_partner_taxa`      where `year` = $year and `month` = $month ";  $update = $mysqli2->query($query);    		
    $query="delete from `google_analytics_partner_summaries` where `year` = $year and `month` = $month ";  $update = $mysqli2->query($query);            
    $query="delete from `google_analytics_summaries`         where `year` = $year and `month` = $month ";  $update = $mysqli2->query($query);            
}//function initialize_tables_4dmonth()


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
    
	$filename = "data/" . $year_month . "/" . "$filename" . "." . $file_extension;
	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		
    
    //print "\n[$i]\n";
    
    return "";
    
}//function save_to_txt($result,$filename,$fields,$year_month,$field_separator,$with_col_header,$file_extension)


function save_eol_monthly_summary($year,$month)
{
    $tab_delim = "";    
    $tab_delim .= $year . chr(9) . $month . chr(9);        
    
    $api = get_from_api(GetNumMonthAsString($month, $year),$year);             
    foreach($api[0] as $label => $value) 
    {            
        $a = date("Y m d", mktime(0, 0, 0, $month, getlastdayofmonth(intval($month), $year), $year)) . " 23:59:59";           
        $b = date("Y m d H:i:s");                        
        //print "<br>$a -- $b<br>";            
        if($a <= $b) $tab_delim .= $value . chr(9); //tab            
    } 
    
    global $mysqli;    

    $query="Select distinct tcn.taxon_concept_id
    FROM taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id) JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id)
    WHERE tcn.vern=0 AND tcn.preferred=1 AND tc.supercedure_id=0 AND tc.published=1";    
    $result = $mysqli->query($query);           
    $taxa_pages = $result->num_rows;    
    
    $query="Select distinct google_analytics_page_stat.taxon_concept_id
    From google_analytics_page_stat where year = $year and month = $month ";    
    $result = $mysqli->query($query);           
    $taxa_pages_viewed = $result->num_rows;
    
    $tab_delim .= $taxa_pages . chr(9) . $taxa_pages_viewed;
 
    //start saving...    
    $fp=fopen("temp.txt","w");fwrite($fp,$tab_delim);fclose($fp);
    $update = $mysqli->query("LOAD DATA LOCAL INFILE 'temp.txt' INTO TABLE google_analytics_summaries");        
    //
}//function save_eol_monthly_summary($year)



?>