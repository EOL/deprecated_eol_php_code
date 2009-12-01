<?php

/*
define("ENVIRONMENT", "slave_32");        //where stats are stored
define("DEBUG", false);
define("MYSQL_DEBUG", false);
require_once("../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];
*/


set_time_limit(0);

/*
http://chart.apis.google.com/chart?
chs=250x100
&chd=t:60,40
&cht=p3
&chl=Hello|World
*/



//exit;
$title = "Total number of pages with names not in CoL";
$arr = get_values_fromCSV($title);
$comma_separated = get_comma_separated($arr,",");

$arr = get_values_fromCSV("date");
$date_comma_separated = get_comma_separated($arr,"|");


print"$comma_separated <hr>
<img src='http://chart.apis.google.com/chart?
chs=1000x300
&amp;chtt=$title
&amp;cht=lc
&amp;chd=t:$comma_separated
&amp;chds=151000,200000
&amp;chl=$date_comma_separated'
alt='Sample chart' /><hr>";

function get_comma_separated($arr,$sep)
{
    $str="";
    for ($i = 0; $i < count($arr); $i++) 
    {	
        $str .= "$arr[$i]$sep";
    }
    $str=trim($str);
    $str=substr($str,0,strlen($str)-1);
    return $str;    
}




function get_values_fromCSV($title)
{
    $filename = "saved_stats.csv";
    $row = 0;
    if(!($handle = fopen($filename, "r")))return;
    
    $label=array();
    $arr = array();
    
    while (($data = fgetcsv($handle)) !== FALSE) 
    {
        //if($row > 0) //to bypass first row, which is the row for the labels
        if($row > -1)
        {                
            $num = count($data);
            //echo "<p> $num fields in line $row: <br /></p>\n";        
            for ($c=0; $c < $num; $c++) 
            {        
                if($row==0) $label[]=$data[$c];
				else        $arr["$label[$c]"][]=$data[$c];
                //else        $arr["$label[$c]"][]=log10($data[$c])/10;                
                /*
                if($c==0)$arr["date"]                   =$data[$c];
                if($c==1)$arr["time"]                   =$data[$c];
                */
            }                        
            //if($row == 10)break;    
        }
        $row++;
    }//end while

    $label = delete_null_in_array($label);
    
    /*
    print_r($label); print "<hr>";
    print_r($arr); print "<hr>";
    */    


    return $arr["$title"];

}//end function


function delete_null_in_array($arr)
{
    foreach ($arr as $key => $value) 
    {if (is_null($value) or trim($value)=='')unset($arr[$key]);}
    return $arr;
}

function get_val_var($v)
{
    if         (isset($_GET["$v"])){$var=$_GET["$v"];}
    elseif     (isset($_POST["$v"])){$var=$_POST["$v"];}
    
    if(isset($var)){return $var;}
    else    {return NULL;}    
}


?>


