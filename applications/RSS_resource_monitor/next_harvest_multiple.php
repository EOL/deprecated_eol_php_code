<?php
$i=0;		
while($result && $row=$result->fetch_assoc())
{
    $tmp="
    <table border='1' cellpadding='2' cellspacing='0'>
    <tr align='center'>
    <td colspan='3'>Harvest</td>			
    </tr>
    <tr align='center'>
    <td>Last</td>
    <td>Next</td>
    <td>Status</td>
    </tr>";		    
    
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
    <td align='center'>$row[last_harvest]</td>
    <td align='center'>$next_harvest</td>				
    <td align='center'>$status</td>
    </tr>";
    $row_link = $row["link"];
    $tmp .= "</table>";	
    $items .= '<item>
    			 <title>' . $row["title"] .'</title>
    			 <link>'. $row["link"] .'</link>
    			 <description><![CDATA['. $tmp .']]></description>
    		   </item>';
}//end while
?>