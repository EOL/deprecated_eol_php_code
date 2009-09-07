<?php
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

/*
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html#d4Ecommerce
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceCommonCalculations.html#revenue
*/

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

        /*
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
        */



function get_from_api($month,$year)
{
    $start_date = "$year-$month-01";
    $end_date   = "$year-$month-" . getlastdayofmonth(intval($month), $year);           
    
    $start_date = "2009-07-01";
    $end_date   = "2009-08-01";


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
        
        while($continue == true)
        {
            $data = $api->data($id, 'ga:pagePath' , 'ga:pageviews,ga:uniquePageviews,ga:bounces,ga:entrances,ga:exits,ga:timeOnPage'       
                    ,false ,$start_date ,$end_date 
                    ,10000 ,$start_count ,false ,false);//96480
            $start_count += 10000;                    
            $val=array();            
            print "no. of records = " . count($data) . "<br>";            
            if(count($data) == 0)$continue=false;
            foreach($data as $metric => $count) 
            {
                $i++; print "$i. ";
                
                /*                
                if(true)
                {
                    if($count["ga:entrances"] > 0)  $bounce_rate = number_format($count["ga:bounces"]/$count["ga:entrances"]*100,2);
                    else                            $bounce_rate = "";
                    
                    if($count["ga:pageviews"] > 0)  $percent_exit = number_format($count["ga:exits"]/$count["ga:pageviews"]*100,2);
                                                    $percent_exit = "";
                                                    
                    if($count["ga:pageviews"] - $count["ga:exits"] > 0)  $averate_time_on_page = $api->sec2hms(number_format($count["ga:timeOnPage"]/($count["ga:pageviews"] - $count["ga:exits"]),2) ,false);        
                                                                         $averate_time_on_page = "";
                    echo " -- " . $bounce_rate;
                    echo " -- " . $percent_exit;
                    echo " -- " . $averate_time_on_page;
                                    
                    //echo "<br>";
                    //echo "$metric: <hr> ";

                    print " | ga:entrances = " . $count["ga:entrances"];
                    print " | pageviews = " . $count["ga:pageviews"] ;
                    print " | uniquePageviews = " . $count["ga:uniquePageviews"] ;
                    print " | exits = " . $count["ga:exits"];
                    print " | url = " . $metric;
                    
                    //print " | count = " . count($count) . "";
                }
                print "<hr>";
                */
                
            }//end for loop

            //exit;
                        
        }//end while
        
        
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

