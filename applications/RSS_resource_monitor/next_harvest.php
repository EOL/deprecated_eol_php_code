<?php
$i=0;		
$tmp="
<table border='1' cellpadding='2' cellspacing='0'>
<tr align='center'>
<td rowspan='2'>Content Partner &nbsp;&nbsp; (n=$result->num_rows)</td>
<td colspan='3'>Harvest</td>			
</tr>
<tr align='center'>
<td>Last</td>
<td>Next</td>
<td>Status</td>
</tr>";		
while($result && $row=$result->fetch_assoc())
{
    $i++;
    $today = date("Y-m-d");			
    $last_harvest = date_create($row["last_harvest"]);
    $next_harvest = date_create($row["next_harvest"]);			
    $last_harvest = date_format($last_harvest,"Y-m-d");
    $next_harvest = date_format($next_harvest,"Y-m-d");
    if($row["refresh_period_hours"] > 0)
    {
        if($today > $next_harvest)
        {
    	    $status="need to re-harvest";					
        	if($last_harvest == $next_harvest){$status="on-time";}					
        }
        else $status="on-time";
    }
    else $status="import once";
    
    if($row["next_harvest"] == $row["last_harvest"]){$next_harvest="no schedule";}
    else{$next_harvest = $row["next_harvest"];}
    $tmp .= "
    <tr>
    <td><a href='http://www.eol.org/administrator/content_partner_report/show/" . $row["agent_id"] ."'>$row[title]</a></td>
    <td align='center'>$row[last_harvest]</td>
    <td align='center'>$next_harvest</td>				
    <td align='center'>$status</td>
    </tr>";    
    $row_link = $row["link"];
}//end while

$tmp .= "</table>";
$items .= '<item>
<title>' . '' .'</title>
<link></link>
<description><![CDATA['. $tmp .']]></description>
</item>';		
?>