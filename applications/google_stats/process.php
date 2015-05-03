<?php
//$GLOBALS['ENV_NAME'] = "slave";

//error_reporting(0);

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_once('google_proc.php');
$mysqli =& $GLOBALS['mysqli_connection'];

$start_cnt = ""; if(isset($_REQUEST['start_cnt'])) $start_cnt = $_REQUEST['start_cnt'];
if(!$start_cnt)$start_cnt=1;

$path=""; if(isset($_REQUEST['path'])) $path = $_REQUEST['path'];

$provider_to_process = ""; if(isset($_REQUEST['provider'])) $provider_to_process = $_REQUEST['provider'];
$agentID="";    if(isset($_REQUEST['agentID'])) $agentID = $_REQUEST['agentID'];
$agent_id="";   if(isset($_REQUEST['agent_id'])) $agent_id = $_REQUEST['agent_id'];
if($agent_id != "")$agentID = $agent_id;
if($agentID != "")
{
    $provider_to_process = get_agentName($agentID);
    if($provider_to_process == "")exit("<hr><i>Data is not available for this content partner.</i><hr>");
}
//else Data is not available for this content partner
$report=""; if(isset($_REQUEST['report'])) $report = $_REQUEST['report'];
$year=""; if(isset($_REQUEST['year'])) $year = $_REQUEST['year'];
if($report != "year2date" and $report != "monthly_stat")
{
    //start - stats for entire eol
    $eol_CountOfTaxaPages = getCountOfTaxaPages('',$path,"eol");
    $eol_total_taxon_id   = count_rec($path . "/query12.csv");
    $eol_total_taxon_id--; //subtract 1 so not to count the column title
    $arr = process_all_eol($path . "/query10.csv");
    $arr = explode(",",$arr);
    $eol_total_unique_page_views    = @$arr[0];
    $eol_total_page_views           = @$arr[1];
    $eol_total_time_on_page_seconds = @$arr[2];
    //end - stats for entire eol
}
if($report == "eol")// www.eol.org monthly report
{
    $comma_separated = "$path,$eol_CountOfTaxaPages,$eol_total_taxon_id,$eol_total_unique_page_views,$eol_total_page_views,$eol_total_time_on_page_seconds";
    print eol_month_report($comma_separated);
    exit;
}
if($report == "year2date")
{
    $website=""; if(isset($_REQUEST['website'])) $website = $_REQUEST['website'];
    $temp = monthly_tabular($year,NULL,$website,NULL);exit;
}
if($report == "monthly_stat")
{
    $month="";
    if(isset($_REQUEST['month'])) $month = $_REQUEST['month'];

    $website = $_REQUEST['website'];
    $report_type = $_REQUEST['report_type'];
    $entire_year = $_REQUEST['entire_year'];
    $temp = monthly_tabular($year,$month,$website,$report_type,$entire_year);exit;
}
if($report == "save_monthly")
{
    $temp = save_monthly(); exit;
}
$filename = $path . "/site_statistics.csv";
$provider               = array();
$page_views             = array();
$unique_page_views      = array();
$time_on_page_seconds   = array();
$taxa_id                = array();
$row = 0;
if(!($handle = fopen($filename, "r")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
  exit;
}
while (($data = fgetcsv($handle)) !== FALSE)
{
    if($row > 0)
    {
        $num = count($data);
        for ($c=0; $c < $num; $c++)
        {
            if($c==0)$agentName                     =$data[$c];
            if($c==1)$taxon_id                      =$data[$c];
            if($c==2)$scientificName                =$data[$c];
            if($c==3)$commonNameEN                  =$data[$c];
            if($c==4)$total_page_views              =$data[$c];
            if($c==5)$total_unique_page_views       =$data[$c];
            if($c==6)$total_time_on_page_seconds    =$data[$c];
        }
        if($provider_to_process != "")
        {
            if($provider_to_process == trim($agentName))$continue=1;
            else                                        $continue=0;
        }
        else $continue=1;
        if($continue)
        {
            $provider[$agentName]=true;
            $page_views["$agentName"][]           =$total_page_views;
            $unique_page_views["$agentName"][]    =$total_unique_page_views;
            $time_on_page_seconds["$agentName"][] =$total_time_on_page_seconds;
            $taxa_id["$agentName"][]              =$taxon_id;
        }
    }
    $row++;
}
fclose($handle);
if($provider_to_process == "")print "Providers = " . sizeof($provider) . "<br>";
$provider = array_keys($provider);
for ($i = 0; $i < count($provider); $i++)
{
    $total_page_views           = compute($provider[$i],$page_views,"sum");
    $total_unique_page_views    = compute($provider[$i],$unique_page_views,"sum");
    $total_time_on_page_seconds = compute($provider[$i],$time_on_page_seconds,"sum");
    $temp_arr = $taxa_id[$provider[$i]];
    $temp_arr = array_unique($temp_arr);
    $total_taxon_id             = count($temp_arr);

    //start get "Count of Taxa Pages"
    $CountOfTaxaPages = getCountOfTaxaPages($provider[$i],$path,"partner");

    //start title
    $title = build_title_from_path($path);

    print "
    <table border='1' cellpaddin=2 cellspacing=0>
    <tr align='center'>
            <td bgcolor='aqua'><b>$title <u>$provider[$i]</u> Statistics</b>
            <br>";
            show_dropdown();
            print"
            </td>
            <td>Taxa Pages with <br> Provider Content</td>
            <td>EOL Site</td>
            <td>Provider <br> Percentage</td>
    </tr>
    <tr>    <td>Count of Taxa Pages</td>
            <td align='right'>" . number_format($CountOfTaxaPages) . "</td>
            <td align='right'>" . number_format($eol_CountOfTaxaPages) . "</td>
            <td align='right'>" . number_format($CountOfTaxaPages/$eol_CountOfTaxaPages*100,2) . "%</td>

    </tr>
    <tr>    <td>Count of Taxa Pages that were viewed during the month</td><td align='right'>" . number_format($total_taxon_id) . "</td>
            <td align='right'>" . number_format($eol_total_taxon_id) . "</td>
            <td align='right'>" . number_format($total_taxon_id/$eol_total_taxon_id*100,2) . "%</td>
    </tr>

    <tr>    <td>Total Unique Page Views for the Month</td>
            <td align='right'>" . number_format($total_unique_page_views) . "</td>
            <td align='right'>" . number_format($eol_total_unique_page_views) . "</td>
            <td align='right'>" . number_format($total_unique_page_views/$eol_total_unique_page_views*100,2) . "%</td>
    </tr>
    <tr>    <td>Total Page Views for the Month</td>
            <td align='right'>" . number_format($total_page_views) . "</td>
            <td align='right'>" . number_format($eol_total_page_views) . "</td>
            <td align='right'>" . number_format($total_page_views/$eol_total_page_views*100,2) . "%</td>

    </tr>
    <tr>    <td>Total Time on Pages for the Month (hours)</td>
            <td align='right'>" . number_format($total_time_on_page_seconds/60/60,2) . "</td>
            <td align='right'>" . number_format($eol_total_time_on_page_seconds/60/60) . "</td>
            <td align='right'>" . number_format(($total_time_on_page_seconds/60/60)/($eol_total_time_on_page_seconds/60/60)*100,2) . "%</td>
    </tr>

    <tr>
        <td colspan=4><i><font size='2'>
        Of the " . number_format($eol_CountOfTaxaPages) . " species pages on the EOL site,
        " . number_format($CountOfTaxaPages) . " or " . number_format($CountOfTaxaPages/$eol_CountOfTaxaPages*100,2) . "% had content provided by " . $provider[$i] . ".
        <br>
        Of the " . number_format($eol_total_taxon_id) . " species pages viewed during the month,
        " . number_format($total_taxon_id) . " or " . number_format($total_taxon_id/$eol_total_taxon_id*100,2) . "% had content provided by " . $provider[$i];
        print"
        <br>
        Visitors spent a total of " . number_format($total_time_on_page_seconds/60/60,1) . " hours on species pages with " . $provider[$i] . " content, representing
        " . number_format(($total_time_on_page_seconds/60/60)/($eol_total_time_on_page_seconds/60/60)*100,2) . "% of the total time spent on the EOL site.
        </font></i>
        </td>
    </tr>";

    if($provider_to_process == "")
    {
        $agentID = get_agentID($provider[$i]);
        if($agentID != "") print"<tr><td colspan=4><font size='2'> <a href='process.php?path=" . $path . "&agentID=$agentID'> See entire report &gt;&gt; </a></td></tr>";
        else               print"<tr><td colspan=4><font size='2'> <a href='process.php?path=" . $path . "&provider=" . urlencode($provider[$i]) . "'> See entire report* &gt;&gt; </a></td></tr>";
    }

    if($provider_to_process != "")print"<tr><td colspan='4' align='center'> " . record_details($provider[$i],$path,$start_cnt,$total_taxon_id,$agentID) . "</td></tr>";
    print"</table>";
    if($provider_to_process == "")print "<hr>";
}
function show_dropdown()
{
    global $provider_to_process;
    global $path;
    global $agentID;

    $arr = get_month_list();
    print"<form action='process.php' method='get'>
    <input type='hidden' name='provider' value='$provider_to_process'>
    <input type='hidden' name='agentID' value='$agentID'>
    <select name='path' onchange='submit()'>";
    foreach ($arr as $year_month)
    {
        print"<option value='data/$year_month'";
        if($path == "data/$year_month")print"selected";
        print">$year_month";
    }
    print"</select></form>";
}

function get_month_list()
{
    $year_now = date("Y");
    $month_now = date("m") - 1;
    $date_end = date("Y-m-d", mktime(0, 0, 0, $month_now, 1, $year_now));
    $date_start = "2009-07-01";
    $var_time = strtotime($date_start);
    $arr=array();
    while ($date_start != $date_end)
    {
        $var_time += 86400;
        $date_start = date("Y-m-d", $var_time);
        $temp = date("Y_m", $var_time);
        $arr["$temp"]=1;
    }
    $arr=array_keys($arr);
    return $arr;
}

function save_monthly()
{
    $filename = "data/monthly.csv";
    $temp = array("2008","2009","2010","2011");
    for($i = 0; $i < count($temp) ; $i++)
    {
        $year = $temp[$i];
        for($month = 1; $month <= 12 ; $month++)
        {
            $mon = GetNumMonthAsString($month, $year);
            $a = date("Y m d", mktime(0, 0, 0, $mon, 1, $temp[$i]));
            $b = date("Y m d", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));
            if($a <= $b)
            {
                $needle = "$year" . "_" . "$mon"; print "$year " . $mon . " -- ";
                $haystack = getMonthYear();
                if(strval(strripos($haystack, $needle)) == "")
                {
                    $api = get_from_api($mon,$year);
                    $str='';
                    $str .= "$year" . "_" . "$mon" . ",";
                    foreach($api[0] as $label => $value)
                    {
                        $unit="";
                        if(in_array($label, array("Percent Exit","Bounce Rate","Percent New Visits")))$unit="%";
                        $str .= "$value$unit,";
                    }
                    $str .= "\n";
                    if($fp = fopen($filename,"a+")){fwrite($fp,$str);fclose($fp);print " Successfully saved ";}
                }
                else print " already saved ";
                print "<br>";

            }
        }
    }
}
function getMonthYear()
{
    $filename = "data/monthly.csv";
    if(!($handle = fopen($filename, "a+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
      return "";
    }
    $comma_separated='';
    while (($data = fgetcsv($handle)) !== FALSE)
    {
        $num = count($data);
        for ($c=0; $c < $num; $c++)
        {
            if($c==0)$comma_separated .= trim($data[$c]) . " " ;
        }
    }

    if(strlen($comma_separated) > 0) $comma_separated = trim(substr($comma_separated,0,strlen($comma_separated)-1));

    //start build up header if no entries yet, ONCE ONLY //==========================================
    if($comma_separated == "")
    {
        $arr=array();
        $arr[]='Year_Month';
        $arr[]='Visits';
        $arr[]='Visitors';
        $arr[]='Pageviews';
        $arr[]='Unique Pageviews';
        $arr[]='Average Pages/Visit';
        $arr[]='Average Time on Site';
        $arr[]='Average Time on Page';
        $arr[]='Percent New Visits';
        $arr[]='Bounce Rate';
        $arr[]='Percent Exit';
        $str="";
        for ($i = 0; $i < count($arr); $i++)
        {
            $str .= $arr[$i] . ",";
        }
        $str .= "\n";
        fwrite($handle,$str);
    }
    //end build up header if no entries yet, ONCE ONLY //==========================================
    fclose($handle);
    return trim($comma_separated);
}

function monthly_tabular($year,$month=Null,$website=Null,$report_type=Null,$entire_year=Null)
{
    print"<table cellpadding='4' cellspacing='0' border='1' >";
    if($month)
    {
        $month_start = $month;
        $exit_after_first_rec=true;
    }
    else
    {
        $month_start = 1;
        $exit_after_first_rec=false;
    }
    if($entire_year) $exit_after_first_rec=true;
    if($year < date("Y"))   $month_limit=12;
    else                    $month_limit = date("n");
    $tab_delim = "";
    $ctr=0;
    if($website=="both")$arr=array("eol","fishbase");
    else                $arr=array($website);
    foreach ($arr as &$website)
    {
        for ($month = $month_start; $month <= $month_limit; $month++)
        {
            $tab_delim .= $year . chr(9) . $month . chr(9);

            if($report_type == "visitors_overview" or $report_type == NULL) $api = get_from_api(GetNumMonthAsString($month, $year),$year,$website);
            elseif(in_array($report_type, array("top_content","subcontinent","continent","country","region","city",
                                                "visitor_type",
                                                "content_title",
                                                "land_pages",
                                                "exit_pages",
                                                "referring_sites",
                                                "referring_engines",
                                                "referring_all",
                                                "q1","q2","q3","browser","os","flash"
                                                ))) $api = get_from_api_Report(GetNumMonthAsString($month, $year),$year,$website,$report_type,$entire_year);
            $month_str = date("F", mktime(0, 0, 0, $month, 1, $year));
            print"<tr bgcolor='aqua' align='center'>";
            $ctr++;
            if($ctr == 1)
            {
                if($report_type == "visitors_overview" or $report_type == NULL) print"<td>$year</td>";
                else
                {
                    print"<td>$year";
                    if(!$entire_year)print"<br>$month_str";
                    print"</td>";
                }

                foreach($api[0] as $label => $value)
                {
                    print"<td>$label</td>";
                }
            }
            print"</tr>";
            $k=0;
            foreach($api as &$api2)
            {
                $k++;
                if($report_type == "visitors_overview" or $report_type == NULL) print"<tr><td align='center'> " . $month_str . "</td>";
                else                                                            print"<tr><td align='right'> " . $k . ".</td>";
                foreach($api2 as $label => $value)
                {
                    $a = date("Y m d", mktime(0, 0, 0, $month, Functions::last_day_of_month(intval($month), $year), $year)) . " 23:59:59";
                    $b = date("Y m d H:i:s");
                    if($a <= $b) $tab_delim .= $value . chr(9); //tab
                    $unit="";
                    $align="right";
                    if(in_array($label, array("Percent Exit","Bounce Rate","Percent New Visits","% New Visits"
                    ,"% Exit","% of ending the session","% Total Visits")))$unit="%";
                    if(in_array($label, array("Visits","Visitors","Pageviews","Unique Pageviews","Entrances","Bounces", "Exits")))$value=number_format($value);
                    if(in_array($label, array("Page","Source","Page Title","Landing Page","Exit Page"
                        ,"Source: Referring Sites"
                        ,"Source: Search Engines"
                        ,"All Traffic Sources"
                        ,"Visitor Type"
                        ,"Continent"
                        ,"Sub-Continent"
                        ,"Country"
                        ,"Region"
                        ,"City"
                        ,"Browser","Operating System","Flash Versions"
                    )))$align="left";
                    $display="$value$unit";
                    if(in_array($label, array("Page","Landing Page","Exit Page")))
                    {
                        $display=substr($value,0,50);
                        if    ($website == "fishbase")$domain="www.fishbase.org";
                        elseif($website == "eol")$domain="www.eol.org";
                        $display="<a target='external' href='http://$domain" . strip_tags($value) . "'>$display</a>";
                    }
                    print"<td align='$align'>$display</td>";
                }
                $tab_delim .= "\n";
                print"</tr>";
            }//2nd loop
            if($exit_after_first_rec)break;
        }//loop month //inner loop
    }//outer loop
    print"</table>";
}//function monthly_tabular($year)
function getCountOfTaxaPages($provider,$path,$for)
{
    $filename = $path . "/query9.csv";
    $row = 0;
    if(!($handle = fopen($filename, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
      return;
    }
    while (($data = fgetcsv($handle)) !== FALSE)
    {
        if($row > 0)
        {
            $num = count($data);
            for ($c=0; $c < $num; $c++)
            {
                if($c==0)$all_taxa_count      =$data[$c];
                if($c==1)$agentName           =$data[$c];
                if($c==2)$agent_taxa_count    =$data[$c];
            }
            if($for == 'partner')if($provider == $agentName) return $agent_taxa_count;
            if($for == 'eol')                                return $all_taxa_count;
        }
        $row++;
    }
}
function compute($provider,$arr,$operation)
{
    $arr = $arr["$provider"];
    if($operation == "sum")     return array_sum($arr);
    if($operation == "count")   return count($arr);
}
function iif($condition, $true, $false)
{
    if($condition)return $true;
    else return $false;
}
function count_rec($file)
{
    $row = 0;
    if(!($handle = fopen($file, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $file);
      return;
    }
    while (($data = fgetcsv($handle)) !== FALSE)
    {
        if($row > 0) $num = count($data);
        $row++;
    }
    return $row;
}
function process_all_eol($file)
{
    $page_views = 0;
    $unique_page_views = 0;
    $time_on_page_seconds = 0;
    $bounce_rate = 0;
    $percent_exit = 0;
    $row = 0;
    if(!($handle = fopen($file, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $file);
      return;
    }
    while (($data = fgetcsv($handle)) !== FALSE)
    {
        if($row > 0)
        {
            $num = count($data);
            for ($c=0; $c < $num; $c++)
            {
                if($c==6)$page_views += intval($data[$c]);
                if($c==7)$unique_page_views += $data[$c];
                if($c==8)$time_on_page_seconds += $data[$c];
                if($c==9)$bounce_rate += $data[$c];
                if($c==10)$percent_exit += $data[$c];
            }
        }
        $row++;
    }
    return "$unique_page_views,$page_views,$time_on_page_seconds";
}
function record_details($provider,$path,$start_cnt,$total_taxon_id,$agentID)
{
    $step=100;
    if($start_cnt == "all"){$start_cnt=1;$max_cnt=999999999;}
    else $max_cnt = $start_cnt+$step;

    //[$total_taxon_id][$path]
    $str="<table style='font-size : small;' align='center'>
    <tr><td colspan='7'>" . $provider . " Taxa Pages ";
    //Top 100

    //start paging ==============================================================
    if($max_cnt < $total_taxon_id)
    {
        $next_step=$step;
        if  (   $max_cnt >= $total_taxon_id-$step   and
                $max_cnt <= $total_taxon_id
            )$next_step = $total_taxon_id - $max_cnt + 1;


        $str .= " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        if($agentID != "")
        {
            $str .= "<a href='process.php?path=" . $path . "&agentID=" . $agentID . "&start_cnt=$max_cnt'>Next $next_step</a> &nbsp;|&nbsp;
                     <a href='process.php?path=" . $path . "&agentID=" . $agentID . "&start_cnt=all'>All</a> ";
        }
        else
        {
            $str .= "<a href='process.php?path=" . $path . "&provider=" . urlencode($provider) . "&start_cnt=$max_cnt'>Next $next_step</a> &nbsp;|&nbsp;
                     <a href='process.php?path=" . $path . "&provider=" . urlencode($provider) . "&start_cnt=all'>All</a> ";
        }
    }
    //end paging ==============================================================

    $str .= "</td></tr>
    <tr align='center'>
    <td>Rank</td>
    <td>Taxon ID</td>
    <td>Scientific Name</td>
    <td>Common Name</td>
    <td>Total <br> Page Views</td>
    <td>Total Unique <br> Page Views</td>
    <td>Total Time <br> On Page Seconds</td>
    </tr>";

    $provider_cnt=0;
    $filename = $path . "/site_statistics.csv";
    $row = 0;
    if (!($handle = fopen($filename, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
      return;
    }
    while (($data = fgetcsv($handle)) !== FALSE)
    {
        if($row > 0 )
        {
            $num = count($data);
            for ($c=0; $c < $num; $c++)
            {
                if($c==0)$agentName                     =$data[$c];
                if($c==1)$taxon_id                      =$data[$c];
                if($c==2)$scientificName                =$data[$c];
                if($c==3)$commonNameEN                  =$data[$c];
                if($c==4)$total_page_views              =$data[$c];
                if($c==5)$total_unique_page_views       =$data[$c];
                if($c==6)$total_time_on_page_seconds    =$data[$c];
            }
            if(trim($agentName) == trim($provider))
            {
                $provider_cnt++;
                if($provider_cnt >= $start_cnt)
                {

                    if ($provider_cnt % 2 == 0){$vcolor = 'white';}
                    else                       {$vcolor = '#ccffff';}
                    $str .= utf8_encode("<tr bgcolor=$vcolor>
                        <td align='right'>" . number_format($provider_cnt) . "</td>
                        <td>$taxon_id</td>
                        <td><i>$scientificName</i></td>
                        <td>$commonNameEN</td>
                        <td align='right'>" . number_format($total_page_views) . "</td>
                        <td align='right'>" . number_format($total_unique_page_views) . "</td>
                        <td align='right'>" . number_format($total_time_on_page_seconds) . "</td>
                    </tr>");
                }
            }
            if($provider_cnt == $max_cnt-1)break; //break 1
        }
        $row++;
    }//end while
    $str .= '</table>';
    return $str;
}

function GetMonthString($m,$y)
{
    $timestamp = mktime(0, 0, 0, $m, 1, $y);
    return date("F Y", $timestamp);
}



function build_title_from_path($path)
{
    $arr = explode("/",$path);
    $arr = explode("_",$arr[1]);
    $y = $arr[0];
    $m = intval($arr[1]);
    $title = GetMonthString($m,$y);
    return $title;
}

function get_month_year_from_path($path)
{
    $arr = explode("/",$path);
    $arr = explode("_",$arr[1]);
    return $arr;
}

function eol_month_report($arr)
{
    $arr = explode(",",$arr);
    $path                           = $arr[0];
    $eol_CountOfTaxaPages           = $arr[1];
    $eol_total_taxon_id             = $arr[2];
    $eol_total_unique_page_views    = $arr[3];
    $eol_total_page_views           = $arr[4];
    $eol_total_time_on_page_seconds = $arr[5];
    $title = build_title_from_path($path);

    //start get stats from google api
    $arr = get_month_year_from_path($path);
    $year = $arr[0];
    $month = $arr[1];
    $api = get_from_api($month,$year);
    //end get stats from google api

    $str="<table border='1' cellpadding=3 cellspacing=0>
    <tr align='center'><td bgcolor='aqua' colspan='3'><b>$title www.eol.org Statistics</b></td></tr>
    <tr align=''>
            <td colspan='2' align='center'>From Google Analytics Summary Page</td>
            <td><font size='2'><i>" . "Definitions <a href='http://www.google.com/adwords/learningcenter/text/38069.html'>source</a>" . "</i></font></td>
    </tr>";

    $label_arr=array(
            "Visits" => "",
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

    foreach($api[0] as $label => $value)
    {
        $unit="";
        if(in_array($label, array("Percent Exit" , "Bounce Rate", "Percent New Visits")))$unit="%";
        if(in_array($label, array("Visits","Visitors","Pageviews","Unique Pageviews")))$value=number_format($value);
        $str .= "
        <tr>
            <td>$label</td>
            <td align='right'>$value$unit</td>
            <td><font size='2'><i>" . $label_arr["$label"] . "</i></font></td>
        </tr>";
    }

    if($eol_total_page_views > 0)
    {
        $str .= "
        <tr align='center'><td colspan='2'>Calculated from the Google Analytics Detail</td></tr>
        <tr><td>Total Page Views</td><td align='right'>" . number_format($eol_total_page_views) . "</td></tr>
        <tr><td>Total Unique Page Views</td><td align='right'>" . number_format($eol_total_unique_page_views) . "</td></tr>
        <tr><td>Total Time on Pages (hours)</td><td align='right'>" . number_format($eol_total_time_on_page_seconds/60/60) . "</td></tr>
        <tr><td>Total EOL Taxa Pages</td><td align='right'>" . number_format($eol_CountOfTaxaPages) . "</td></tr>
        <tr>
            <td>Viewed Taxa Pages</td>
            <td align='right'>" . number_format($eol_total_taxon_id) . "</td>
            <td align='left'>&nbsp;" . number_format($eol_total_taxon_id/$eol_CountOfTaxaPages*100,2) . "%</td>
        </tr>";
    }
    $str .= "</table>";
    $str .= "<br>" . record_details_eol($path) . "";
    return $str;
}

function record_details_eol($path)
{
    $str="<table style='font-size : small;' align='center' border='1' cellpadding='3' cellspacing='0'>
    <tr><td colspan='10'><b>Top 100 Pages</b></td></tr>
    <tr align='center'>
    <td>Rank</td>
    <td>Taxon ID</td>
    <td>URL</td>
    <td>Scientific Name</td>
    <td>Common Name</td>
    <td>Page Views</td>
    <td>Unique Page Views</td>
    <td>Time On Page Seconds</td>
    <td>Bounce Rate</td>
    <td>Percent Exit</td>
    </tr>";

    $provider_cnt=0;
    $filename = $path . "/query10.csv";
    $row = 0;
    if(!($handle = fopen($filename, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
      return;
    }
    while (($data = fgetcsv($handle)) !== FALSE)
    {
        if($row > 0 )
        {
            $num = count($data);
            for ($c=0; $c < $num; $c++)
            {
                if($c==0)$id                =$data[$c];
                if($c==1)$date_added        =$data[$c];
                if($c==2)$taxon_id          =$data[$c];
                if($c==3)$url               =$data[$c];
                if($c==4)$scientificName    =$data[$c];
                if($c==5)$commonNameEN      =$data[$c];
                if($c==6)$page_views        =$data[$c];
                if($c==7)$unique_page_views     =$data[$c];
                if($c==8)$time_on_page_seconds  =$data[$c];
                if($c==9)$bounce_rate           =$data[$c];
                if($c==10)$percent_exit         =$data[$c];
            }

            $provider_cnt++;
            if ($provider_cnt % 2 == 0){$vcolor = 'white';}
            else                       {$vcolor = '#ccffff';}
            $str .= utf8_encode("<tr bgcolor=$vcolor>
                <td align='right'>" . number_format($provider_cnt) . "</td>
                <td align='center'>$taxon_id&nbsp;</td>
                <td>$url</td>
                <td><i>$scientificName</i> &nbsp;</td>
                <td>$commonNameEN &nbsp;</td>
                <td align='right'>" . number_format($page_views) . "</td>
                <td align='right'>" . number_format($unique_page_views) . "</td>
                <td align='right'>" . sec2hms($time_on_page_seconds ,false) . "</td>
                <td align='right'>" . number_format($bounce_rate,2) . "</td>
                <td align='right'>" . number_format($percent_exit,2) . "</td>
            </tr>");
            if($provider_cnt == 100)break; //break 2
        }
        $row++;
    }//end while
    $str .= '</table>';
    return $str;
}

function get_agentID($agentName)
{
    global $mysqli;
    $agentName = str_ireplace("'" , "''", $agentName);
    $query="SELECT a.id, a.full_name, a.display_name, a.updated_at, a.created_at, a.agent_status_id, he.id FROM agents a JOIN agents_resources ar ON a.id = ar.agent_id JOIN harvest_events he ON ar.resource_id = he.resource_id WHERE a.full_name = '$agentName' ORDER BY he.id Desc";
    $sql = $mysqli->query($query);
    $row = $sql->fetch_row();
    //print " [" . $row[0] . "] ";
    return $row[0];
}

function get_agentName($agentID)
{
    global $mysqli;
    $query="SELECT a.full_name, a.display_name, a.updated_at, a.created_at, a.agent_status_id, he.id FROM agents a JOIN agents_resources ar ON a.id = ar.agent_id JOIN harvest_events he ON ar.resource_id = he.resource_id WHERE a.id = '$agentID' ORDER BY he.id Desc";
    $sql = $mysqli->query($query);
    $row = $sql->fetch_row();
    return $row[0];
}

function sec2hms($sec, $padHours = false)
{
    $hms = "";
    $hours = intval(intval($sec) / 3600);
    $hms .= ($padHours)
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
          : $hours. ':';
    $minutes = intval(($sec / 60) % 60);
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
    $seconds = intval($sec % 60);
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
    return $hms;
}

function hms2sec ($hms)
{     list($h, $m, $s) = explode (":", $hms);
      $seconds = 0;
      $seconds += (intval($h) * 3600);
      $seconds += (intval($m) * 60);
      $seconds += (intval($s));
      return $seconds;
}
?>