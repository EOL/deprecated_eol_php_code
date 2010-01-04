<?php

$csv = array("site_statistics.csv","query9.csv","query10.csv","query12.csv");

$temp = array("2008","2009","2010");
//$temp = array("2008","2009");
for($i = 0; $i < count($temp) ; $i++) 
{
    print"<a href='index.php?year=" . $temp[$i] . "'>$temp[$i]</a> | ";
}
print"<hr>";


$year = get_val_var('year');
if($year=="")$year="2009";


print"<table cellpadding='6' cellspacing='0'>";
for($month = 1; $month <= 12 ; $month++) 
{
    $a = date("Y m d", mktime(0, 0, 0, $month, 1, $year));
    $b = date("Y m d");
    
    if($a <= $b)
    {
        $path = "data/" . date("Y_m", mktime(0, 0, 0, $month, 1, $year));
        
        //print "<hr>$path<hr>";

        $csv_available=0;
        for($j = 0; $j < count($csv); $j++) 
        {
            $file = $path . "/" . $csv[$j];
            if(file_exists($file)) $csv_available++;
        }

        print"<tr>        
        <td>" . date("Y M", mktime(0, 0, 0, $month, 1, $year)) . "</td>";
        
        if($csv_available == 0) print"<td>Providers</td>";
        else                    print"<td><a href='process.php?path=" . $path . "'>Providers</a>        </td>";
        
        print"<td><a href='process.php?path=" . $path . "&report=eol'>www.eol.org</a>    </td>";        
        
        if($csv_available == 0)     $str="<font color='red'>Individual provider data not available.</font>";
        elseif($csv_available < 4)  $str="Individual provider data (some) not available";
        else                        $str="Individual provider data available";
    
        if($csv_available < 4) $str .= " 
        <a href='generate.php?month=$month&year=$year'>Generate (1-2-3)</a> |         

        <a href='start1.php?month=$month&year=$year'>Step 1 (gaps)</a>
        <a href='start2.php?month=$month&year=$year'>Step 2 (qry 1-8)</a>
        <a href='start3.php?month=$month&year=$year'>Step 3 (qry 9-12 CSV files)</a> | 
        
        <a href='generate_monthly_stats.php?month=$month&year=$year'>Generate monthly stats</a>        

        ";     
        /*     
        
        Prepare files
        */
        
        print"<td><i><font size='2'>$str</font></i></td>";    
        
        print"    
        </tr>
        ";        
    }
    //print "$a $b <br>";
}

print"
<tr><td colspan='4'><a href='process.php?report=year2date&year=$year'>View $year summary <i>(from online www.google.com/analytics)</i></a></td></tr>
<tr><td colspan='4'>
<a href='process.php?report=save_monthly&year=$year'>Save monthly summaries</a> | 
<a href='data/monthly.csv'>View</a>
</td></tr>
";




function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}

//print "<hr>";

?> 