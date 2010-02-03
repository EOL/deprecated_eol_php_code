<?php
// the VIEW
// form result after submit
    /* 
        Expects:
            $stats
    */
?>

<HTML>
    <HEAD>
        <TITLE>Species Stats</TITLE>
    </HEAD>
    <BODY>

<?php

//$group=1;
$sep = chr(9);
$rd = "";        


/* working well
foreach($stats as $taxon_concept_id => $stat)
{
    print "Taxon ID: $taxon_concept_id:<br/>";
    foreach($stat as $key => $value)
    {
        print "&nbsp;&nbsp;$key: $value<br/>";
    }
    print "<br/>";
}
*/

if(is_array($stats)) 
{
    //print "<hr>an array<hr>";
    $arr = $stats;
    if      ($arr[1]=="data_objects_more_stat")  published_data_objects($arr[0]); //group 4
    elseif  ($arr[1]=="lifedesk_stat")          lifedesk_stat($arr[0]); //group 5
}
else
{
    exit("<hr>- wrong data format -<hr>");
    //no one is going here anymore
	//print "<hr>not an array<hr>";
    $comma_separated = $stats;
    $arr = explode(",",$comma_separated);
}

/*
print "<br>Number of params returned: " . count($arr) . "<br>"; 
if(count($arr)==26) published_data_objects($arr); //group 4
if(count($arr)==15) lifedesk_stat($arr); //group 5
*/


function lifedesk_stat($stats)
{       //print"<pre>";print_r($stats);print"</pre>";

        $total_published_taxa=$stats["totals"][0];
        $total_published_do=$stats["totals"][1];
        $total_unpublished_taxa=$stats["totals"][2];
        $total_unpublished_do=$stats["totals"][3];
        
        $total_taxa = $total_published_taxa + $total_unpublished_taxa;
        $total_do = $total_published_do + $total_unpublished_do;
        
        $provider=$stats;
    
        //start display
        $arr = array_keys($provider["published"]);
        print"<p style='font-family : Arial;'>
        These are LifeDesk providers who have registered in the <a target='eol_registry' href='http://www.eol.org/administrator/content_partner_report'>EOL Content Partner Registry</a>.<br>
        </p>
                
        <table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Narrow;'>
        <tr align='center'><td colspan='3'>LifeDesks</td></tr>
        <tr align='center'>
            <td>Published (n=" . count($arr) . ")</td>
            <td>Taxa pages</td>
            <td>Data objects</td>
        </tr>
        ";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print " <tr>
                        <td>$arr[$i]</td>
                        <td align='right'>" . $provider["published"][$arr[$i]][0] . "</td>
                        <td align='right'>" . $provider["published"][$arr[$i]][1] . "</td>
                    </tr>
                  ";
        }
        print"  <tr align='right'>
                    <td>Total:</td>
                    <td>$total_published_taxa</td>
                    <td>$total_published_do</td>
                </tr>";
        //print"</table>";        
        /////////////////////////////////////////////////////////////////////////////////////////////////
        $arr = array_keys($provider["unpublished"]);
        print"
        <tr align='center'>
            <td>Un-published (n=" . count($arr) . ")</td>
            <td>Taxa pages</td>
            <td>Data objects</td>
        </tr>";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print " <tr>
                        <td>$arr[$i]</td>
                        <td align='right'>" . $provider["unpublished"][$arr[$i]][0] . "</td>
                        <td align='right'>" . $provider["unpublished"][$arr[$i]][1] . "</td>
                    </tr>
                  ";
        }
        print"  <tr align='right'>
                    <td>Total:</td>
                    <td>$total_unpublished_taxa</td>
                    <td>$total_unpublished_do</td>
                </tr>
                <tr align='right' bgcolor='aqua'>
                    <td>Total:</td>
                    <td>$total_taxa</td>
                    <td>$total_do</td>
                </tr>
                ";
        
        /////////////////////////////////////////////////////////////////////////////////////////////////
        /*
        $arr = array_keys($provider["unpublished"]);
        print"
        <tr align='center'>
            <td>Unpublished (n=" . count($arr) . ")</td><td colspan='2'>&nbsp;</td>
        </tr>
        ";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print " <tr>
                        <td>$arr[$i]</td><td colspan='2'>&nbsp;</td>
                    </tr>
                  ";
        }
        */
        print"</table>";        
        print("<font size='2'>{as of " . date('Y-m-d H:i:s') . "} ");
        print" &nbsp;&nbsp;&nbsp; <a href='javascript:self.close()'>Exit</a></font>";
        
        //end display

    print"
    <p style='font-family : Arial;'>
    This is the current list of available LifeDesks. <a href='http://www.lifedesks.org/sites/'>More info</a>
    </p>";
    get_values_fromCSV();

}

function get_values_fromCSV()
{
    //convert to csv    
    $filename="http://admin.lifedesks.org/files/lifedesk_admin/lifedesk_stats/lifedesk_stats.txt";
    //$filename="http://127.0.0.1/lifedesk_stats.txt";
    
    $OUT = fopen("temp.csv", "w+");            
    $str = Functions::get_remote_file($filename);    
    if($str)
    {
        $str = str_ireplace(',', '&#044;', $str);
        $str = str_ireplace(chr(9), ',', $str);
        fwrite($OUT, $str);        
        fclose($OUT);
    }
    $filename = "temp.csv";
    //end convert to csv    
    
    //start reads csv    
    $row = 0;
    if(!($handle = fopen($filename, "r")))return;
    
    $label=array();
    $arr = array();
    
    print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Narrow;'>";
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        if($row == 0) //to get first row, first cell
        {
            print $data[0];    
        }
               
        print"<tr>";                
        //if($row > -1)   //not to bypass first row
        if($row > 0) //to bypass first row, which is the row for the labels
        {                
            $num = count($data);
            //print $num;
            //echo "<p> $num fields in line $row: <br /></p>\n";        
            $num = $row-1;
            if($row == 1)   print"<td align='center'>#</td>";            
            else            print"<td align='right'>" . $num . "</td>";
            //for ($c=0; $c < $num; $c++) 
            for ($c=0; $c < 10; $c++) 
            {        
                $align='center';
                if($row > 0)if(in_array($c, array(3,6,7,8,9)))$align='right';                
                
                print"<td align='$align'>";
                if($c == 1 and $row > 1) print"<a href='$data[$c]'>$data[$c]</a>";
                else                     print $data[$c];
                print"</td>";                
            }                        
            //if($row == 10)break;    
        }
        $row++;
        print"</tr>";
    }//end while
    print"</table>";
    
    return "";

}//end function


function published_data_objects($arr)
{
	//global $arr;
	
    print"Published Data Objects: <br/>";
    $flickr_count = $arr[24];
    $user_do_count = $arr[25];
	/* debug
	print "<hr>
    $flickr_count <br>
    $user_do_count <br>		
	<hr>";
	print_r($arr);
	*/
	//exit;
	
    array_pop($arr);    
    array_pop($arr);        
    
    $data_type = array(
    1 => "Image"      , 
    2 => "Sound"      , 
    3 => "Text"       , 
    4 => "Video"      , 
    5 => "GBIF Image" , 
    6 => "IUCN"       , 
    7 => "Flash"      , 
    8 => "YouTube"    
    );
    $vetted_type = array( 
    1 => array( "id" => "0" , "label" => "Unknown"),
    2 => array( "id" => "4" , "label" => "Untrusted"),
    3 => array( "id" => "5" , "label" => "Trusted")
    );                

    for ($j = 1; $j <= count($data_type); $j++) 
    {
        $sum[$j]=0;
    }  

    print"
    <table cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Narrow;'>    
    <tr align='center'>";
        for ($i = 1; $i <= count($data_type); $i++) 
        {
            print"<td colspan='3'>" . $data_type[$i] . "</td>";
        }      
    print"</tr>";
    
    print"
    <tr align='center'>";
    $k=0;
    for ($j = 1; $j <= count($data_type); $j++) 
    {
        for ($i = 1; $i <= count($vetted_type); $i++) 
        {
            print"<td>" . $vetted_type[$i]['label'] . "</td>";
            $sum[$j] = $sum[$j] + $arr[$k];
            $k++;
        }      
    }  
    print"</tr>";

    print"
    <tr align='center'>";
        for ($i = 0; $i < count($arr); $i++) 
        {
            print"<Td align='right'>" . $arr[$i] . "</td>";
        }
    print"</tr>";

    print"
    <tr align='center'>";
    $k=0;
    for ($j = 1; $j <= count($data_type); $j++) 
    {
        print"<td colspan='3' align='right'>" . number_format($sum[$j]) . "</td>";            
    }  
    print"</tr>";
    print"    
    </table>       
    <br> Total published data objects = " . number_format(array_sum($sum)) . "    
    <br> Latest Flickr harvest count = " . number_format($flickr_count) . "    
    <br> User-submitted data objects = " . number_format($user_do_count) . "<br>";    
    
    print("<font size='2'>{as of " . date('Y-m-d H:i:s') . "}</font>");
    
    print"<br><font size='2'><br> <a href='javascript:self.close()'>Exit</a></font>";
}//end func
        
?>
        
<!---
<?php
if($group != 3)
{
    $fileidx = time();
    $filename ="temp/" . $fileidx . ".txt"; 
    $fp = fopen($filename,"a"); // $fp is now the file pointer to file $filename
    if($fp)
    {
        fwrite($fp,$rd);    //    Write information to the file
        fclose($fp);        //    Close the file
        
        /* temporarily commented
        echo "<hr><i>Use a spreadsheet to open the tab-delimited TXT file created for ";
        print "<a target='_blank' href=$filename> - Download - </a>
        </i>";
        */
        
    } 
    else 
    {
        echo "Error saving file!";
    }
}
?>
<?php
//start save table
//end save table
?>
--->



        
    </BODY>
</HTML>