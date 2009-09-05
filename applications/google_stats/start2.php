<?php
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

    $label_arr=array(
            "Visits" => "",
            "Source" => "",
            
            "Visitors" => "",
            "Pageviews" => "The total number of times the page was viewed across all visits.",
            "Unique Pageviews" => "Unique Pageviews does not count repeat visits to a page.",
            "Average Pages/Visit" => "",
            "Average Time on Site" => "",
            "Average Time on Page" => "The average amount of time that visitors spent on (a) page.",
			"Percent New Visits" => "",			
	        "Bounce Rate" => "The percentage of entrances on the page that result in the person immediately leaving the site.",
            "Percent Exit" => "The percentage of visitors leaving your site immediately after viewing that page."
            );


$month = '07'; $year = '2009';
$api = get_from_api($month,$year);    

        print"<table cellpadding='4' cellspacing='0' border='1'>";
        print"<tr bgcolor='aqua' align='center'>";
        if($month == 1)
        {
            print"<td>$year</td>";
            foreach($api[0] as $label => $value) 
            {            
                print"<td>$label</td>";
            } 
            
        }
        print"</tr>";        
        
        print"<tr><td align='center'> " . date("F", mktime(0, 0, 0, $month, 1, $year)) . "</td>";
        
        foreach($api[0] as $label => $value) 
        {            
            $unit="";
            if(in_array($label, array("Percent Exit","Bounce Rate","Percent New Visits")))$unit="%";
            if(in_array($label, array("Visits","Visitors","Pageviews","Unique Pageviews")))$value=number_format($value);
            print"<td align='right'>$value$unit</td>";
        } 
        print"</tr></table>";



function get_from_api($month,$year)
{
    $start_date = "$year-$month-01";
    $end_date   = "$year-$month-" . getlastdayofmonth(intval($month), $year);           
    
    $start_date = "2009-06-23";
    $end_date   = "2009-07-23";


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
        if(true) 
        {
            $data = $api->data($id, 'ga:pagePath'   , 'ga:pageviews',false ,$start_date ,$end_date ,10      ,1    ,false,false);
            $val=array();
            foreach($data as $metric => $count) 
            {
                echo "$metric: $count <br>";
                $val[$metric]=$count;
            }                        
            exit;
            $final[0]["Visits"]                 = $val["ga:visits"];        
            $final[0]["Source"]                 = $val["ga:pagePath"];        
            
            /*
            $final[0]["Visitors"]               = $val["ga:visitors"];        
            $final[0]["Pageviews"]              = $val["ga:pageviews"];                             
            $final[0]["Average Pages/Visit"]    = number_format($val["ga:pageviews"]/$val["ga:visits"],2);        
            $final[0]["Average Time on Site"]   = $api->sec2hms($val["ga:timeOnSite"]/$val["ga:visits"] ,false);                    
    		$temp_percent_new_visits            = number_format($val["ga:newVisits"]/$val["ga:visits"]*100,2);			
			$temp_bounce_rate                   = number_format($val["ga:bounces"]/$val["ga:entrances"]*100,2);
            $temp_percent_exit                  = number_format($val["ga:exits"]/$val["ga:pageviews"]*100,2);                        
            */
            

            
        }
        
        
    }
    else 
    {
        echo "login failed <br>";    
    }

    return $final;
}//end function
function getlastdayofmonth($month, $year) 
{
    return idate('d', mktime(0, 0, 0, ($month + 1), 0, $year));
}


?>

