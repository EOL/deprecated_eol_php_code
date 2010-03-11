<?php

$csv = array("site_statistics.csv","query9.csv","query10.csv","query12.csv");

$moreStats = get_val_var('moreStats');

$temp = array("2008","2009","2010");
for($i = 0; $i < count($temp) ; $i++) 
{
    print"<a href='index.php?year=" . $temp[$i] . "&moreStats=$moreStats'>$temp[$i]</a> | ";
}
print"<hr>";


$year = get_val_var('year');
if($year=="")$year=date("Y");



/*
if($moreStats)print"morestats";
else print"not morestats";
exit;
*/

print"<table cellpadding='6' cellspacing='0' border='1'>";

print"
<form name='fn1' method='get' action='#'>
    <input id='rad_eol' type='radio' name='organization' value='eol' checked>EOL
    <input id='rad_fbs' type='radio' name='organization' value='fishbase'>FishBase
    <input id='rad_bot' type='radio' name='organization' value='both'>Both
    <hr>
    <input id='rad_rep1' type='radio' name='report' value='visitors_overview' checked>Visitors Overview
    <input id='rad_rep2' type='radio' name='report' value='top_content'>Top Content
    <input id='rad_rep3' type='radio' name='report' value='referring_sites'>Referring Sites
</form>";
?>

<script language="javascript1.2">
function get_organization(i)
{
    if( document.getElementById('rad_eol').checked )eval("document.forms.f" + i + ".website.value = document.getElementById('rad_eol').value")
    if( document.getElementById('rad_fbs').checked )eval("document.forms.f" + i + ".website.value = document.getElementById('rad_fbs').value")
    if( document.getElementById('rad_bot').checked )eval("document.forms.f" + i + ".website.value = document.getElementById('rad_bot').value")
    
    if( document.getElementById('rad_rep1').checked )eval("document.forms.f" + i + ".report_type.value = document.getElementById('rad_rep1').value")
    if( document.getElementById('rad_rep2').checked )eval("document.forms.f" + i + ".report_type.value = document.getElementById('rad_rep2').value")
    if( document.getElementById('rad_rep3').checked )eval("document.forms.f" + i + ".report_type.value = document.getElementById('rad_rep3').value")
    
    eval("document.forms.f" + i + ".submit()")
}
</script>

<?php

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
        
        if(!$moreStats)
        {
            if($csv_available == 0) print"<td>Providers</td>";
            else                    print"<td><a href='process.php?path=" . $path . "'>Providers</a>        </td>";                
            print"<td><a href='process.php?path=" . $path . "&report=eol'>www.eol.org</a>    </td>";                
            if($csv_available == 0)     $str="<font color='red'>Individual provider data not available.</font>";
            elseif($csv_available < 4)  $str="Individual provider data (some) not available";
            else                        $str="Individual provider data available";        
            /*             
            if($csv_available < 4) $str .= " 
            <a href='generate.php?month=$month&year=$year'>Generate (1-2-3)</a> |        
            ";     
            $str .= " <a href='generate_monthly_stats.php?month=$month&year=$year'>Generate monthly stats</a> ";        
            Prepare files
            <a href='start1.php?month=$month&year=$year'>Step 1 (gaps)</a>
            <a href='start2.php?month=$month&year=$year'>Step 2 (qry 1-8)</a>
            <a href='start3.php?month=$month&year=$year'>Step 3 (qry 9-12 CSV files)</a> |         
            */
            print"<td><i><font size='2'>$str</font></i></td>";    
        }        
        else
        {
            print"
            <form name='f$month' action='process.php?report=monthly_stat&year=$year&month=$month' method='post'>
            <td><i><font size='2'></font></i>                        
                <input type='hidden' name='website' value='eol'>
                <input type='hidden' name='report_type' value='visitors_overview'>                
                <input type='button' value='Go' onClick='get_organization($month)'>                        
            </td>
            </form>";       
        }        
        
        print"</tr>";        
    }
    //print "$a $b <br>";

}//end for loop

    
if(!$moreStats)
{
    print"<tr><td colspan='4'><a href='process.php?report=year2date&year=$year'>
    View $year summary <i>(from online www.google.com/analytics)</i></a></td></tr>";
    print"<tr><td colspan='4'>
    <a href='process.php?report=save_monthly&year=$year'>Save monthly summaries</a> | 
    <a href='data/monthly.csv'>View</a>
    </td></tr>";
}
else
{
    print"<tr>
    <form name='f173' action='process.php?report=monthly_stat&year=$year' method='post'>
    <td colspan='2'><i><font size='2'></font></i>                        
        <input type='text' name='website' value='eol'>
        <input type='text' name='report_type' value='visitors_overview'>                
        <input type='button' value='$year Summary' onClick='get_organization(173)'>                        
    </td>
    </form>
    </tr>
    ";       
}


function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    else   return NULL;                            
    return $var;    
}

//print "<hr>";

?> 