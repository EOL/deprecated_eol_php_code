<?php
//start test display | and start calling each functions
//test feb 6 sunday
print"<table border='1'>";
for ($s=0; $s <= sizeof($wsheet)-1; $s++) 
{
    print "<tr bgcolor='silver'><td>" . $wsheet[$s] . "</td></tr>";
    $fields = array_keys($sheet[$s]);
    
    print"<tr bgcolor='aqua'>";    
    for ($t=0; $t <= sizeof($fields)-1; $t++)    //list down all column headings
    {
        print"<td>" . $fields[$t] . "</td>";            
    }
    print"</tr>";
    
    // /*
    print"<tr>";
    for ($t=0; $t <= sizeof($fields)-1; $t++) 
    {        
        $final_arr = $sheet[$s][$fields[$t]];
        print"<td>";        
        for ($u=0; $u <= sizeof($final_arr)-1; $u++) 
        {
            print $final_arr[$u] . " | ";        
        }    
        print"</td>";    
    }    
    print"</tr>";
    // */    
}
print"</table>";
//end test display
/*
//$sheet[5]['Kingdom'] = reorder_index($sheet[5]['Kingdom']);
print"<hr><table border='1'>
<tr><td>" . $sheet[2]['DataObject ID'][0] . "</td>
    <td>" . $sheet[2]['DateCreated'][0] . "</td>
    <td>" . $sheet[2]['Taxon Name'][0] . "</td>
    </tr>
</table>";
*/
?>