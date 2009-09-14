<?php

//define("ENVIRONMENT", "slave_215");
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

ini_set('memory_limit','1000M');
set_time_limit(0);

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

$path = get_val_var('path');

$provider_to_process = get_val_var('provider');

$agentID = get_val_var('agentID');
if($agentID != "")$provider_to_process = get_agentName($agentID);

$report = get_val_var('report');
$year = get_val_var('year');

if($report != "year2date")
{
    //start - stats for entire eol
    $eol_CountOfTaxaPages = getCountOfTaxaPages('',$path,"eol");
    $eol_total_taxon_id   = count_rec($path . "/query12.csv");    
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
if($report == "year2date")// monthly tabular report
{
    $temp = monthly_tabular($year);
    exit;
}
if($report == "save_monthly")// save monthly
{
    $temp = save_monthly();
    exit;
}
function save_monthly()
{
    /*
$tomorrow  = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
$lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
$nextyear  = mktime(0, 0, 0, date("m"),   date("d"),   date("Y")+1);    
    */
    
    $temp = array("2008","2009");
    for($i = 0; $i < count($temp) ; $i++) 
    {
        for($month = 1; $month <= 12 ; $month++) 
        {
            $a = date("Y m d", mktime(0, 0, 0, $month, 1, $temp[$i]));
            //$b = date("Y m d");   //use this if you want current data; otherwise use previous month.                
            $b = date("Y m d", mktime(0, 0, 0, date("m")-1, date("d"),   date("Y")));                            
            if($a <= $b)
            {                
                //print "$temp[$i] " . substr(strval($month/100),2,2) . "<br>";
            }
        }        
    }
}//function save_monthly()

function num2places($x)
{
    //if(str_lenstrval($x))
}


function monthly_tabular($year)
{
    print"<table cellpadding='4' cellspacing='0' border='1'>";
    
    if($year < date("Y"))$month_limit=12;
    else $month_limit = date("n");
    
    for ($month = 1; $month <= $month_limit; $month++) 
    {
        $temp = $month/10;
        $temp = strval($temp);
        $temp = str_ireplace(".", "", $temp);
        
        $api = get_from_api($temp,$year);    
        
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
        print"</tr>";
        // */        
    }
    print"</table>";
}


$filename = $path . "/site_statistics.csv";
$provider               = array();
$page_views             = array();
$unique_page_views      = array();
$time_on_page_seconds   = array();
$taxa_id                = array();

/*
$t_page_views           =0;
$t_unique_page_views    =0;
$t_time_on_page_seconds =0;
$t_taxa_id =0;
*/

$row = 0;
if(!($handle = fopen($filename, "r")))exit;
while (($data = fgetcsv($handle)) !== FALSE) 
{
    if($row > 0)
    {    
        $num = count($data);
        //echo "<p> $num fields in line $row: <br /></p>\n";        
        for ($c=0; $c < $num; $c++) 
        {        
            //echo $c+1 . "- [[" . $data[$c] . "]]<br />\n";        
            
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
        //if($row == 10)break;    
    }
    $row++;
}
fclose($handle);


if($provider_to_process == "")print "Providers = " . sizeof($provider) . "<br>";

//print_r($provider);

$provider = array_keys($provider);
for ($i = 0; $i < count($provider); $i++) 
{       
    $total_page_views           = compute($provider[$i],$page_views,"sum");
    $total_unique_page_views    = compute($provider[$i],$unique_page_views,"sum");
    $total_time_on_page_seconds = compute($provider[$i],$time_on_page_seconds,"sum");
    
    /*
    $total_taxon_id             = compute($provider[$i],$taxa_id,"count");
    */
    
    ///*
    $temp_arr = $taxa_id[$provider[$i]];
    $temp_arr = array_unique($temp_arr);
    $total_taxon_id             = count($temp_arr);
    //*/
    //print_r($temp_arr);

    //start get "Count of Taxa Pages"
    $CountOfTaxaPages = getCountOfTaxaPages($provider[$i],$path,"partner");
    //end get "Count of Taxa Pages"
    
    //start title    
    $title = build_title_from_path($path);
    //end title
    
    print "    
    <table border='1' cellpaddin=2 cellspacing=0>
    <tr align='center'>    
            <td bgcolor='aqua'><b>$title $provider[$i] Statistics</b></td>
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
        " . number_format($total_taxon_id) . " or " . number_format($total_taxon_id/$eol_total_taxon_id*100,2) . "% had content provided by " . $provider[$i] . ".
        <br>
        Those " . number_format($total_taxon_id) . " species pages were viewed by 
        " . number_format($total_unique_page_views) . " distinct visitors for a total of 
        " . number_format($total_page_views) . " page viewings during the month.
        <br>
        Visitors spent a total of " . number_format($total_time_on_page_seconds/60/60,1) . " hours on species pages with " . $provider[$i] . " content, representing 
        " . number_format(($total_time_on_page_seconds/60/60)/($eol_total_time_on_page_seconds/60/60)*100,2) . "% of the total time spent on the EOL site.
        </font></i>
        </td>
    </tr>";

    
    if($provider_to_process == "")
    {
        print"<tr><td colspan=4><font size='2'><a href='process.php?path=" . $path . "&provider=$provider[$i]'> 1. See entire report &gt;&gt; </a></td></tr>";
        
        $agentID = get_agentID($provider[$i]);
        print"<tr><td colspan=4><font size='2'><a href='process.php?path=" . $path . "&agentID=$agentID'> 2. See entire report &gt;&gt; </a></td></tr>";
    }

    if($provider_to_process != "")print"<tr><td colspan='4' align='center'> " . record_details($provider[$i],$path) . "</td></tr>";
    
    print"</table>";
    
    if($provider_to_process == "")print "<hr>";
}


/*
print"    t_page_views           =  $t_page_views           <br>
          t_unique_page_views    =  $t_unique_page_views    <br>
          t_time_on_page_seconds =  " . $t_time_on_page_seconds/60/60 . " <br>
          t_taxa_id =  $t_taxa_id <br> ";
*/        




////////////////////////////////////////////////////////////////////////
function getCountOfTaxaPages($provider,$path,$for)
{
    $filename = $path . "/query9.csv";
    $row = 0;
    if(!($handle = fopen($filename, "r")))return;
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        if($row > 0)
        {    
            $num = count($data);
            //echo "<p> $num fields in line $row: <br /></p>\n";        
            for ($c=0; $c < $num; $c++) 
            {        
                //echo $c+1 . "- [[" . $data[$c] . "]]<br />\n";                    
                if($c==0)$all_taxa_count      =$data[$c];
                if($c==1)$agentName           =$data[$c];
                if($c==2)$agent_taxa_count    =$data[$c];
            }
            
            if($for == 'partner')if($provider == $agentName) return $agent_taxa_count;
            if($for == 'eol')                                return $all_taxa_count;            
            
            //if($row == 10)break;    
        }
        $row++;
    }//end while
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

function get_val_var($v)
{
    if         (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif     (isset($_POST["$v"])){$var=$_POST["$v"];}    
    if(isset($var))    return $var;
    else            return NULL;    
}

function count_rec($file)
{
    $row = 0;
    
    
    if(!($handle = fopen($file, "r")))return;
    
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        if($row > 0)
        {    
            $num = count($data);
            //echo "<p> $num fields in line $row: <br /></p>\n";        
            for ($c=0; $c < $num; $c++) 
            {                        
            }
        }
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
        
    if(!($handle = fopen($file, "r")))return;
    
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        if($row > 0)
        {    
            $num = count($data);
            //echo "<p> $num fields in line $row: <br /></p>\n";        
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
        //if($row==100)break;
    }    
    
    /*    
    $page_views                =array_sum($page_views);
    $unique_page_views        =array_sum($unique_page_views);
    $time_on_page_seconds    =array_sum($time_on_page_seconds);
    $bounce_rate            =array_sum($bounce_rate);
    $percent_exit            =array_sum($percent_exit);
    */
    
    /* working...
    print"<hr>rows = $row<hr>
    page_views                = " . number_format($page_views)            ."<br>
    unique_page_views        = " . number_format($unique_page_views)        ."<br>
    time_on_page_seconds    = " . number_format($time_on_page_seconds)    ."<br>
    bounce_rate                = " . number_format($bounce_rate)            ."<br>
    percent_exit            = " . number_format($percent_exit)            ."<br>    
    ";
    */
    
    return "$unique_page_views,$page_views,$time_on_page_seconds";
}

function record_details($provider,$path)
{   
    $str="<table style='font-size : small;' align='center'>
    <tr><td colspan='7'>Top 100 " . $provider . " Taxa Pages</td></tr>
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
    $handle = fopen($filename, "r");
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        if($row > 0 )
        {    
            $num = count($data);
            //echo "<p> $num fields in line $row: <br /></p>\n";        
            for ($c=0; $c < $num; $c++) 
            {        
                //echo $c+1 . "- [[" . $data[$c] . "]]<br />\n";                    
                if($c==0)$agentName                     =$data[$c];
                if($c==1)$taxon_id                      =$data[$c];
                if($c==2)$scientificName                =$data[$c];
                if($c==3)$commonNameEN                  =$data[$c];
                if($c==4)$total_page_views              =$data[$c];
                if($c==5)$total_unique_page_views       =$data[$c];
                if($c==6)$total_time_on_page_seconds    =$data[$c];
            }            
            //if($row == 3)break;                            
            if(trim($agentName) == trim($provider))
            {
                $provider_cnt++;                
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
            if($provider_cnt == 100)break;                
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
    /*
    $arr[0] = year  e.g. 1972
    $arr[1] = month e.g. 07
    */
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
    <tr align='center'>    
            <td bgcolor='aqua' colspan='3'><b>$title www.eol.org Statistics</b></td>
    </tr>

    <tr align=''>    
            <td colspan='2' align='center'>From Google Analytics Summary Page</td>
            <td><font size='2'><i>" . "Definitions <a href='http://www.google.com/adwords/learningcenter/text/38069.html'>source</a>" . "</i></font></td>
    </tr>
    ";    
    
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
        </tr>
        ";
    } 

    if($eol_total_page_views > 0)
    {
        $str .= "
        <tr align='center'><td colspan='2'>Calculated from the Google Analytics Detail</td></tr>
        <tr><td>Total Page Views</td><td align='right'>" . number_format($eol_total_page_views) . "</td></tr>
        <tr><td>Total Unique Page Views</td><td align='right'>" . number_format($eol_total_unique_page_views) . "</td></tr>
        <tr><td>Total Time on Pages (hours)</td><td align='right'>" . number_format($eol_total_time_on_page_seconds/60/60) . "</td></tr>
        <tr><td>Total EOL Taxa Pages x</td><td align='right'>" . number_format($eol_CountOfTaxaPages) . "</td></tr>
        <tr>
            <td>Viewed Taxa Pages</td>
            <td align='right'>" . number_format($eol_total_taxon_id) . "</td>
            <td align='left'>&nbsp;" . number_format($eol_total_taxon_id/$eol_CountOfTaxaPages*100,2) . "%</td>            
        </tr>";
    }
    else
    {
        //$str .= "<tr align='center'><td colspan='2'><i></i></td></tr>";
    }    
    $str .= "</table>";    
    $str .= "<br>" . record_details_eol($path) . "";        
    return $str;
}

function record_details_eol($path)
{   
    $str="<table style='font-size : small;' align='center' border='1' cellpadding='3' cellspacing='0'>
    <tr><td colspan='10'><b>Top 100 Pages xx</b></td></tr>
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
    
    if(!($handle = fopen($filename, "r")))return;
    
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
            //if($row == 3)break;                            

            //if(trim($agentName) == trim($provider))
            if(1==1)
            {
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
            }
            if($provider_cnt == 100)break;                
        }                
        $row++;
    }//end while    
    $str .= '</table>';
    return $str;        
}

function get_from_api($month,$year)
{
    //exit(" -- stopx -- ");
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


function get_agentID($agentName)
{
    global $mysqli;
    
    $query="Select agents.id,agents.full_name,agents.display_name,agents.updated_at,
    agents.created_at,agents.agent_status_id,harvest_events.id From
    agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    Where
    agents.full_name = '$agentName'
    Order By harvest_events.id Desc";
    $sql = $mysqli->query($query);
    $row = $sql->fetch_row();            
    //print " [" . $row[0] . "] ";
    return $row[0];
}
function get_agentName($agentID)
{
    global $mysqli;
    
    $query="Select agents.full_name,agents.display_name,agents.updated_at,
    agents.created_at,agents.agent_status_id,harvest_events.id From
    agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    Where
    agents.id = '$agentID'
    Order By harvest_events.id Desc";
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


?>