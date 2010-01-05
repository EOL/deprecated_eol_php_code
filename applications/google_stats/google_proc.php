<?php
function get_from_api($month,$year)
{
    //exit(" -- stopx -- ");
    $start_date = "$year-$month-01";
    $end_date   = "$year-$month-" . getlastdayofmonth(intval($month), $year);           
    
    $final = array();
    
    require_once(LOCAL_ROOT . '/classes/modules/Google_Analytics_API_PHP/analytics_api.php');
    
    $login = GOOGLE_ANALYTICS_API_USERNAME;
    $password = GOOGLE_ANALYTICS_API_PASSWORD;
    //$password .= "eli";
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
    
        //exit;//////////////////////////////////////////////////////////////////////////////////////////////        
         /*
        print"<pre>";
        //print_r ($api->get_summaries($start_date, $end_date, false, true));
        print"</pre>";
            
        $arr = $api->get_summaries($start_date, $end_date, false, false);
        foreach($arr["www.eol.org"] as $metric => $count) 
        {
            echo "$metric: $count <br>";
        } 
         */   
        //exit;//////////////////////////////////////////////////////////////////////////////////////////////        
        
        // get some account summary information without a dimension
        if(true) 
        {
            //==============================================================
            $data = $api->data($id, ''   , 'ga:uniquePageviews',false ,$start_date ,$end_date ,10      ,1    ,false,false);
            $val=array();
            foreach($data as $metric => $count) 
            {
                $val[$metric]=$count;
            }                                    
            $temp_uniquePageviews   = $val["ga:uniquePageviews"];            
            //==============================================================
            $data = $api->data($id, ''   , 'ga:bounces,ga:entrances,ga:exits,ga:newVisits,ga:pageviews,ga:timeOnPage,ga:timeOnSite,ga:visitors,ga:visits',false ,$start_date ,$end_date ,10      ,1    ,false,false);
            $val=array();
            foreach($data as $metric => $count) 
            {
                $val[$metric]=$count;
            }                        
            $final[0]["Visits"]                 = $val["ga:visits"];        
            $final[0]["Visitors"]               = $val["ga:visitors"];        
            $final[0]["Pageviews"]              = $val["ga:pageviews"];                 
            $final[0]["Unique Pageviews"]       = $temp_uniquePageviews;                           
            $final[0]["Average Pages/Visit"]    = number_format($val["ga:pageviews"]/$val["ga:visits"],2);        
            $final[0]["Average Time on Site"]   = $api->sec2hms($val["ga:timeOnSite"]/$val["ga:visits"] ,false);                    
			$temp_percent_new_visits            = number_format($val["ga:newVisits"]/$val["ga:visits"]*100,2);			
			$temp_bounce_rate                   = number_format($val["ga:bounces"]/$val["ga:entrances"]*100,2);
            $temp_percent_exit                  = number_format($val["ga:exits"]/$val["ga:pageviews"]*100,2);             
            //==============================================================
            $data = $api->data($id, ''   , 'ga:timeOnPage,ga:pageviews,ga:exits',false ,$start_date ,$end_date ,10      ,1    ,false,false);
            $val=array();
            foreach($data as $metric => $count) 
            {
                $val[$metric]=$count;
            }                                    
            $final[0]["Average Time on Page"]   = $api->sec2hms($val["ga:timeOnPage"]/($val["ga:pageviews"] - $val["ga:exits"]) ,false);        
            //==============================================================
            $final[0]["Percent New Visits"] = $temp_percent_new_visits;
            //==============================================================			
            $final[0]["Bounce Rate"] = $temp_bounce_rate;
            //==============================================================
            $final[0]["Percent Exit"] = $temp_percent_exit;            
            //==============================================================                                                
        }        
    }
    else echo "login failed <br>";    
    return $final;
}//end function

function getlastdayofmonth($month, $year) 
{
    return idate('d', mktime(0, 0, 0, ($month + 1), 0, $year));
}

function GetNumMonthAsString($m,$y)
{
    $timestamp = mktime(0, 0, 0, $m, 1, $y);    
    return date("m", $timestamp);
}

function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}
?>