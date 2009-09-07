<?php
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);

/*
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html#d4Ecommerce
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
http://code.google.com/apis/analytics/docs/gdata/gdataReferenceCommonCalculations.html#revenue
*/

$month = '07'; $year = '2009';
$api = get_from_api($month,$year);    

function get_from_api($month,$year)
{
    $start_date = "$year-$month-01";
    $end_date   = "$year-$month-" . getlastdayofmonth(intval($month), $year);           
    
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
        $range=10000;
        //$range=10;
        //$start_count=30001;
        
        $OUT = fopen("data/" . $year . "_" . $month . "/google_analytics_page_statistics.txt", "w+");
        $cr = "\n";
        $sep = ",";
        $sep = chr(9); //tab
        $str = "";
        
        while($continue == true)
        {
            $data = $api->data($id, 'ga:pagePath' , 'ga:pageviews,ga:uniquePageviews,ga:bounces,ga:entrances,ga:exits,ga:timeOnPage'       
                    ,false ,$start_date ,$end_date 
                    ,$range ,$start_count ,false ,false);//96480
            $start_count += $range;                    
            $val=array();            
            print "no. of records = " . count($data) . "<br>";            
            
            if(count($data) == 0)$continue=false;
            //$continue=false;
            
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
                                                    
                    if($count["ga:pageviews"] - $count["ga:exits"] > 0)  $averate_time_on_page = $api->sec2hms(number_format($count["ga:timeOnPage"]/($count["ga:pageviews"] - $count["ga:exits"]),2) ,false);        
                    else                                                 $averate_time_on_page = "";
                    
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
                            $averate_time_on_page . $sep . $bounce_rate . $sep . $percent_exit . $sep . $money_index . $sep . date('Y-m-d H:i:s') . $cr;
                }
                print "<hr>";
                // */
                
            }//end for loop

            //exit;
                        
            fwrite($OUT, $str);
        }//end while
        fclose($OUT);
        
        $mysqli2 = load_mysql_environment('eol_statistics');        
        $update = $mysqli2->query("TRUNCATE TABLE eol_statistics.google_analytics_page_statistics");        
        $update = $mysqli2->query("LOAD DATA LOCAL INFILE 'data/" . $year . "_" . $month . "/google_analytics_page_statistics.txt' INTO TABLE eol_statistics.google_analytics_page_statistics");        
        
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

