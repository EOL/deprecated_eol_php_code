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

$group=1;
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

$comma_separated = $stats;
$arr = explode(",",$comma_separated);

print "Number of params returned: " . count($arr) . "<br>"; 

if(count($arr)==26)//group 4 //if(count($arr)==37)//group 4
{      
    print"Published Data Objects: <br/>";
    $flickr_count = $arr[24];
    $user_do_count = $arr[25];
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
    <br> User-submitted data objects = " . number_format($user_do_count) . "    
    <font size='2'><br> <a href='javascript:self.close()'>Exit</a></font>";
    
}//if(count($arr)==24)//group 4

exit("<p><font size='2'>{as of " . date('Y-m-d H:i:s') . "}<br> --- end ---</font>");
        
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