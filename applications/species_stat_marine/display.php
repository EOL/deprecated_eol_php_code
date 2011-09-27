<?php
/* This is almost obsolete as it is no longer pointing to a real production server */
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];
$tbl = "page_stats_marine";
$eol_site = "www.eol.org";
$view = get_val_var('view');
if($view == ""){$view = 1;}
print"<table cellpadding='1' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>
<tr><td>EoL Page Statistics 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i>Beta Version</i>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font size='1'>
<a href='javascript:history.go(-1)'> &lt;&lt; Back to main stats</a>";
$view = 3;
print"</font></td></tr></table><hr>";
if($view != 3){$qry = "select * from $tbl where active = 'y' order by type desc";}
else          {$qry = "select * from $tbl order by date_created desc, time_created desc limit 10";}
$sql = $mysqli->query($qry);
//==================================================================
if($view == 3)
{
    $arr = array();
    $i = 0;
    while( $row = $sql->fetch_assoc() )
    {
        $arr['Run date'][$i] = "$row[date_created]<br>$row[time_created]";        
        /*
        Number of names in WORMS: 135878
        # of those names with EOL pages: 73430
        # of those pages with objects: 24534
        # of those pages with vetted objects: 24505 
        */
        $arr['Number of names in WORMS'][$i]                            = number_format($row["names_from_xml"]);
        $arr['# of those names with EOL pages'][$i]                     = number_format($row["names_in_eol"]);
        $arr['Marine pages'][$i]                                        = number_format($row["marine_pages"]);
        $arr['Number of WORMS species pages with objects'][$i]          = number_format($row["pages_with_objects"]);
        $arr['Number of WORMS species pages with vetted objects'][$i]   = number_format($row["pages_with_vetted_objects"]);
        $i++;
    }
    $label = array_keys($arr);
    print"<table cellpadding='1' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>";
    for ($i = 0; $i < count($arr); $i++) 
    {
        if($i == 0) print "<tr><td><b>THE MARINE DIMENSION</b></td></tr>";
        if($i == 10) print "<tr><td>&nbsp;</td></tr><tr><td><b>Vetted Content Statistics</b></td></tr>";
        if($i == 16) print "<tr><td>&nbsp;</td></tr><tr><td><b>Curatorial Statistics</b></td></tr>";
        if ($i % 2 == 0){$vcolor = 'white';}
        else            {$vcolor = '#ccffff';}
        print "<tr bgcolor=$vcolor>";
        print "<td>$label[$i]</td>";
        for ($k = 0; $k < $sql->num_rows; $k++) print "<td align='right'>" . @$arr[$label[$i]][$k] . "</td><td width='10'>&nbsp;</td>";
        print "</tr>";
    }
    print"</table>";
}
//==================================================================
$sql->close();

print "<hr> 
<table cellpadding='1' cellspacing='0' border='0' style='font-size : x-small; font-family : Arial Unicode MS;'><tr><td>
<font size='1'><i>
-- end --
</i></font>
</td></tr></table>";

function get_val_var($v)
{
    if     (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif (isset($_POST["$v"])){$var=$_POST["$v"];}
    if(isset($var)) return $var;
    else return NULL;
}

?>