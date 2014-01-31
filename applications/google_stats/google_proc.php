<?php
function prepare_vars($website, $month, $year, $entire_year = Null)
{
    if($entire_year)
    {
        $start_date = "$year-01-01";
        $end_date   = "$year-12-" . Functions::last_day_of_month(12, $year);
    }
    else
    {
        $start_date = "$year-$month-01";
        $end_date   = "$year-$month-" . Functions::last_day_of_month(intval($month), $year);
    }
    if($website == "eol" or $website == NULL)
    {   $login = GOOGLE_ANALYTICS_API_USERNAME;
        $password = GOOGLE_ANALYTICS_API_PASSWORD;
        $organization = "www.eol.org";
        $start_date = "$year-$month-01";
        if($start_date < "2011-09-01") $organization = "EOLv1";
        else                           $organization = "EOLv2";
    }
    elseif($website == "fishbase")
    {   $login = "celloran@cgiar.org";
        $password = "kitelloran";
        $organization = "FishBase - All mirrors";
    }
    else
    {   $login = GOOGLE_ANALYTICS_API_USERNAME;
        $password = GOOGLE_ANALYTICS_API_PASSWORD;
        $organization = $website;
    }
    $arr = array( "login"         => $login,
                  "password"      => $password,
                  "organization"  => $organization,
                  "start_date"    => $start_date,
                  "end_date"      => $end_date
                );
    return $arr;
}

function get_from_api_Report($month, $year, $website = NULL, $report, $entire_year)
{
    $arr = prepare_vars($website, $month, $year, $entire_year);
    $login          = $arr["login"];
    $password       = $arr["password"];
    $organization   = $arr["organization"];
    $start_date     = $arr["start_date"];
    $end_date       = $arr["end_date"];
    require_once(DOC_ROOT . 'vendor/Google_Analytics_API_PHP/analytics_api.php');
    $api = new analytics_api();
    if($api->login($login, $password))
    {
        $api->load_accounts();
        $arr = $api->accounts;
        $id = $arr["$organization"]["tableId"];
        if($report == "q1") $data = $api->data($id, 'ga:previousPagePath', 'ga:pageviews,ga:nextPagePath', false, $start_date, $end_date, 10, 1, 'ga:nextPagePath%3d%3dhttp://www.eol.org/index', false);
        if($report == "q2") $data = $api->data($id, 'ga:pagePath', 'ga:exits,ga:uniquePageviews', false, $start_date, $end_date, 10, 1, false, false);
        if($report == "q3") $data = $api->data($id, 'ga:pagePath', 'ga:exits,ga:pageviews'      , false, $start_date, $end_date, 10, 1, false, false);
        if($report == "browser")  
        {   $data = $api->data($id, 'ga:browser', 'ga:visits', false, $start_date, $end_date, 100, 1, false, false);
            $data2 = $api->data($id, '', 'ga:visits', false, $start_date, $end_date, 5000, 1, false, false);
        }
        if($report == "os")  
        {   $data = $api->data($id, 'ga:operatingsystem', 'ga:visits', false, $start_date, $end_date, 100, 1, false, false);
            $data2 = $api->data($id, '', 'ga:visits', false, $start_date, $end_date, 5000, 1, false, false);
        }
        if($report == "flash")  
        {   $data = $api->data($id, 'ga:flashVersion', 'ga:visits', false, $start_date, $end_date, 100, 1, false, false);
            $data2 = $api->data($id, '', 'ga:visits', false, $start_date, $end_date, 5000, 1, false, false);
        }        
        if($report == "top_content")    $data = $api->data($id, 'ga:PagePath', 'ga:pageviews,ga:uniquePageviews,ga:timeOnPage,ga:bounces,ga:entrances,ga:exits', false, $start_date, $end_date, 100, 1, false, false);
        if($report == "content_title")  $data = $api->data($id, 'ga:PageTitle', 'ga:pageviews,ga:uniquePageviews,ga:timeOnPage,ga:bounces,ga:entrances,ga:exits', false, $start_date, $end_date, 100, 1, false, false);
        if($report == "land_pages")  $data = $api->data($id, 'ga:landingPagePath', 'ga:entrances,ga:bounces,ga:exits', false, $start_date, $end_date, 100, 1, false, false);
        if($report == "exit_pages")  
        {
            $data = $api->data($id, 'ga:exitPagePath', 'ga:exits,ga:pageviews', false, $start_date, $end_date, 100, 1, false, false);
            $data2 = $api->data($id, 'ga:PagePath', 'ga:pageviews', false, $start_date, $end_date, 5000, 1, false, false);
        }
        if($report == "referring_sites")    $data = $api->data($id, 'ga:source', 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, 'ga:medium%3d%3dreferral', false);
        if($report == "referring_engines")  $data = $api->data($id, 'ga:source', 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, 'ga:medium%3d%3dorganic', false);
        if($report == "referring_all")      $data = $api->data($id, 'ga:source', 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);

        if($report == "continent")          $data = $api->data($id, 'ga:continent'   , 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);
        if($report == "subcontinent")       $data = $api->data($id, 'ga:subcontinent', 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);
        if($report == "country")            $data = $api->data($id, 'ga:country'     , 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);
        if($report == "region")             $data = $api->data($id, 'ga:region'      , 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);
        if($report == "city")               $data = $api->data($id, 'ga:city'        , 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);
        if($report == "visitor_type")
        {
            $data = $api->data($id, 'ga:visitorType', 'ga:visits,ga:newVisits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:entrances', false, $start_date, $end_date, 10, 1, false, false);
            $data2 = $api->data($id, '', 'ga:visits', false, $start_date, $end_date, 100, 1, false, false);
        }
        $val = array();
        $final = array();
        foreach($data as $metric => $count)
        {
            $val[$metric] = $count;
            if(in_array($report, array("top_content", "content_title")))
            {
               if    ($report == "top_content")   $metric_title = "Page";
               elseif($report == "content_title") $metric_title = "Page Title";
               if($count["ga:entrances"] == 0) $bounce_rate = 0;
               else                            $bounce_rate = number_format($count["ga:bounces"]/$count["ga:entrances"]*100, 2);
               $final[] = array($metric_title           => "<i>" . utf8_decode($metric) . "</i>", 
                                "Pageviews"             => $count["ga:pageviews"],
                                "Unique Pageviews"      => $count["ga:uniquePageviews"],                          
                                "Average Time on Page"  => "'" . $api->sec2hms(round($count["ga:timeOnPage"]/($count["ga:pageviews"] - $count["ga:exits"])), false) . "'",
                                "Bounce Rate"           => $bounce_rate, 
                                "Percent Exit"          => number_format($count["ga:exits"]/$count["ga:pageviews"]*100, 2)
                                );
            }
            if(in_array($report, array("referring_sites", "continent", "subcontinent", "country", "region", "city", "referring_engines", "referring_all")))            
            {
               if($report == "continent")        $metric_title = "Continent";
               elseif($report == "subcontinent") $metric_title = "Sub-Continent";
               elseif($report == "country")      $metric_title = "Country";
               elseif($report == "region")       $metric_title = "Region";
               elseif($report == "city")         $metric_title = "City";
               elseif($report == "referring_sites")   $metric_title = "Source: Referring Sites";
               elseif($report == "referring_engines") $metric_title = "Source: Search Engines";
               elseif($report == "referring_all")     $metric_title = "All Traffic Sources";
               else                                   $metric_title = "-no name-";
               $final[] = array($metric_title           => "<i>" . utf8_decode($metric) . "</i>",
                                "Visits"                => $count["ga:visits"],
                                "Pages/Visit"           => number_format($count["ga:pageviews"]/$count["ga:visits"], 2),
                                "Average Time on Site"  => "'" . $api->sec2hms(round($count["ga:timeOnSite"]/$count["ga:visits"]), false) . "'",
                                "% New Visits"          => number_format($count["ga:newVisits"]/$count["ga:visits"]*100, 2),
                                "Bounce Rate"           => number_format($count["ga:bounces"]/$count["ga:entrances"]*100, 2)
                                );
            }
            if(in_array($report, array("land_pages")))
            {  $final[] = array("Landing Page"          => "<i>" . utf8_decode($metric) . "</i>",
                                "Entrances"             => $count["ga:entrances"],
                                "Bounces"               => $count["ga:bounces"],
                                "Bounce Rate"           => number_format($count["ga:bounces"]/$count["ga:entrances"]*100, 2)
                                );
            }
            if(in_array($report, array("exit_pages")))
            {  $final[] = array("Exit Page"        => "<i>" . utf8_decode($metric) . "</i>",
                                "Exits"            => $count["ga:exits"],
                                "Pageviews"        => $data2[$metric]["ga:pageviews"],
                                "% Exit"           => number_format($count["ga:exits"]/($data2[$metric]["ga:pageviews"])*100, 2));
            }
            if(in_array($report, array("q1")))
            {  $final[] = array("xx"        => "<i>" . utf8_decode($metric) . "</i>", 
                                "pageviews" => $count["ga:pageviews"],
                                "next path" => $count["ga:nextPagePath"] 
                                );
            }
            if(in_array($report, array("q2")))
            {  $final[] = array("Page"                    => "<i>" . utf8_decode($metric) . "</i>", 
                                "Exits"                   => $count["ga:exits"],
                                "Unique Pageviews"        => $count["ga:uniquePageviews"],
                                "% of ending the session" => number_format($count["ga:exits"]/$count["ga:uniquePageviews"]*100, 2)
                                );
            }
            if(in_array($report, array("q3")))
            {  $final[] = array("Page"                    => "<i>" . utf8_decode($metric) . "</i>", 
                                "Exits"                   => $count["ga:exits"],
                                "Pageviews"               => $count["ga:pageviews"],
                                "% of ending the session" => number_format($count["ga:exits"]/$count["ga:pageviews"]*100, 2)
                                );
            }
            if(in_array($report, array("browser")))
            {  $final[] = array("Browser"           => "<i>" . utf8_decode($metric) . "</i>", 
                                "Visits"            => $count["ga:visits"],
                                "% Total Visits"                 => number_format($count["ga:visits"]/$data2["ga:visits"]*100, 2)
                                );
            }
            if(in_array($report, array("os")))
            {  $final[] = array("Operating System"  => "<i>" . utf8_decode($metric) . "</i>", 
                                "Visits"            => $count["ga:visits"],
                                "% Total Visits"                 => number_format($count["ga:visits"]/$data2["ga:visits"]*100, 2)
                                );
            }
            if(in_array($report, array("flash")))
            {  $final[] = array("Flash Versions"    => "<i>" . utf8_decode($metric) . "</i>", 
                                "Visits"            => $count["ga:visits"],
                                "% Total Visits"    => number_format($count["ga:visits"]/$data2["ga:visits"]*100, 2)
                                );
            }
            if(in_array($report, array("visitor_type")))
            {
               if($report == "visitor_type") $metric_title = "Visitor Type";
               else                          $metric_title = "-no name-";               
               $final[] = array($metric_title           => "<i>" . utf8_decode($metric) . "</i>",                
                                "Visits"                => $count["ga:visits"],
                                "% Total Visits"        => number_format($count["ga:visits"]/$data2["ga:visits"]*100, 2),
                                "Pages/Visit"           => number_format($count["ga:pageviews"]/$count["ga:visits"],2),
                                "Average Time on Site"  => "'" . $api->sec2hms(round($count["ga:timeOnSite"]/$count["ga:visits"]) ,false) . "'",
                                "% New Visits"          => number_format($count["ga:newVisits"]/$count["ga:visits"]*100, 2),
                                "Bounce Rate"           => number_format($count["ga:bounces"]/$count["ga:entrances"]*100, 2)
                                );
            }
            //===========================
        }
    }
    else echo "\n -login failed- \n";
    return $final;
}

function get_from_api($month, $year, $website = NULL)
{
    $arr = prepare_vars($website, $month, $year);
    $login        = $arr["login"];
    $password     = $arr["password"];
    $organization = $arr["organization"];
    $start_date   = $arr["start_date"];
    $end_date     = $arr["end_date"];
    require_once(DOC_ROOT . 'vendor/Google_Analytics_API_PHP/analytics_api.php');
    $id = '';
    $api = new analytics_api();
    if($api->login($login, $password))
    {
        if(true)
        {
            $api->load_accounts();
            $arr = $api->accounts;
        }
        $id = $arr["$organization"]["tableId"];
        // get some account summary information without a dimension
        //==============================================================
        $data = $api->data($id, '', 'ga:uniquePageviews', false, $start_date, $end_date, 10, 1, false, false);
        $val = array();
        foreach($data as $metric => $count) $val[$metric] = $count;
        $temp_uniquePageviews = $val["ga:uniquePageviews"];
        //==============================================================
        $data = $api->data($id, '', 'ga:bounces,ga:entrances,ga:exits,ga:newVisits,ga:pageviews,ga:timeOnPage,ga:timeOnSite,ga:visitors,ga:visits', false, $start_date, $end_date, 10, 1, false, false);
        $val = array();
        $final = array();
        foreach($data as $metric => $count) $val[$metric] = $count;
        $final[0]["Visits"]                 = $val["ga:visits"];
        $final[0]["Visitors"]               = $val["ga:visitors"];
        $final[0]["Pageviews"]              = $val["ga:pageviews"];
        $final[0]["Unique Pageviews"]       = $temp_uniquePageviews;
        $final[0]["Average Pages/Visit"]    = number_format($val["ga:pageviews"]/$val["ga:visits"], 2);
        $final[0]["Average Time on Site"]   = "'" . $api->sec2hms(round($val["ga:timeOnSite"]/$val["ga:visits"]), false) . "'";
        $temp_percent_new_visits            = number_format($val["ga:newVisits"]/$val["ga:visits"]*100, 2);
        $temp_bounce_rate                   = number_format($val["ga:bounces"]/$val["ga:entrances"]*100, 2);
        $temp_percent_exit                  = number_format($val["ga:exits"]/$val["ga:pageviews"]*100, 2);
        //==============================================================
        $data = $api->data($id, '', 'ga:timeOnPage,ga:pageviews,ga:exits', false, $start_date, $end_date, 10, 1, false, false);
        $val = array();
        foreach($data as $metric => $count) $val[$metric] = $count;
        $final[0]["Average Time on Page"]   = "'" . $api->sec2hms(round($val["ga:timeOnPage"]/($val["ga:pageviews"] - $val["ga:exits"])), false) . "'";
        $final[0]["Percent New Visits"]     = $temp_percent_new_visits;
        $final[0]["Bounce Rate"]            = $temp_bounce_rate;
        $final[0]["Percent Exit"]           = $temp_percent_exit;
    }
    else echo "\n -login failed- \n";
    return $final;
}

function GetNumMonthAsString($m,$y)
{
    $timestamp = mktime(0, 0, 0, $m, 1, $y);
    return date("m", $timestamp);
}
?>