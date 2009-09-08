<?php

$csv = array("site_statistics.csv","query9.csv","query10.csv","query12.csv");

$temp = array("2008","2009");
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
        
        if($csv_available == 0)     $str="<font color='red'>CSV files are not available.</font>";
        elseif($csv_available < 4)  $str="Some CSV files are not available";
        else                        $str="All CSV files are available";
    
        if($csv_available < 4) $str .= " 
        Prepare files
        <a href='start1.php?month=$month&year=$year'>Step 1</a>
        <a href='start2.php?month=$month&year=$year'>Step 2</a>
        ";
        
        print"<td><i><font size='2'>$str</font></i></td>";    
        
        print"    
        </tr>
        ";        
    }
    //print "$a $b <br>";
}

print"<tr><td colspan='2'><a href='process.php?report=year2date&year=$year'>Year Summary: $year</a></td></tr>";

function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}

//print "<hr>";

?> 