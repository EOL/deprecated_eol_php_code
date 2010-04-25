<?php

require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$eol_site = "www.eol.org";

$report = 'list';	//original functionality

$returns 	= get_val_var('return');
$sort_order = get_val_var('sort');
$vetted     = get_val_var('vetted');
$sciname_4color = "";


$list 			= get_val_var('list');

$separator 		= get_val_var('separator');
$choice 			= get_val_var('choice');
$choice2 			= get_val_var('choice2');
$data_kind 		= get_val_var('data_kind');

$withCSV 			= get_val_var('withCSV');
$format 			= get_val_var('format');	//1 = info items		2 = objects title

if($choice2 == 1){$head = "Found in EoL";}
if($choice2 == 2){$head = "Not found in EoL";}
if($choice2 == 3){$head = "With data objects";}
if($choice2 == 4){$head = "Without data objects";}
if($choice2 == 5){$head = "Find 'Family'";}

$with_name_source 			= get_val_var('with_name_source');

if(trim($choice) == ""){
print"<i>Please paste your list of names inside the box. <br>Select a filter and separator then click 'Submit'.</i>";
exit;
}

$rd	= "";	//row data
$cr 	= "\n";
$tab = chr(9);
if($separator == '')
{
	switch (true)
	{
	case $choice == 1:  $separator = chr(13);break;
	case $choice == 2:  $separator = chr(10);break;	
	case $choice == 3:  $separator = chr(9);break;	
	case $choice == 4:  $separator = ',';break;	
	default:break;
	}	
}	
	
$list = $separator . $list;	//weird behavior - first char must be the separator
$arr = explode("$separator", $list);	
$orig_lenth_of_arr = count($arr);	
$arr = array_unique($arr);	//print "<hr>";	print_r($arr);	//exit;
$arr = array_trim($arr,$orig_lenth_of_arr);	// $orig_lenth_of_arr --- this is the length of array after explode function
sort($arr); //ksort($arr);

print "<font size='2' face='courier'>Total no. of names submitted: " . " " . count($arr) . "</font><hr>";
if(count($arr) == 0){exit;}
//exit;
print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";

$us = "&#153;";	//unique separator

$value_list="";


//$domain="labs1.eol.org";
//$domain="staging.eol.org";
$domain="www.eol.org";

$api_put_species="http://$domain/api/search/";
$api_put_taxid_1="http://$domain/api/pages/";
$api_put_taxid_2="?images=75&text=75&subjects=all";
//$api_put_taxid_2 .= "&vetted=$vetted";    

//http://staging.eol.org/api/pages/206692?images=75&text=75&subjects=all

/*
http://www.eol.org/api/pages/206692?images=75&text=75&subjects=all&vetted=1
http://www.eol.org/api/search/gadus morhua

*/

$arr_table=array();
foreach($arr as $sciname)
{
	//print"$sciname <br>";//debug
    $file = $api_put_species . urlencode($sciname);
    //print"<hr>$file<hr>";
    $xml = Functions::get_hashed_response($file);
    $arr_details = get_details($xml,$sciname);
    $arr_details = sort_deatils($arr_details);
    //print"<pre>";print_r($arr_details);print"</pre>";//debug
    $arr_table = array_merge($arr_details,$arr_table);    
}
//print"<pre>";print_r($arr_table);print"</pre>";//debug
show_table($arr_table);
//print"<pre>";print_r($arr_details);print"</pre>";
//exit("-- end --");//debug
?>

<!--- ################################################################################################################# --->
<!--- ################################################################################################################# --->
<!--- ################################################################################################################# --->
<!--- ################################################################################################################# --->

<?php

function show_table($arr)
{
    print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : x-small; font-family : Arial Unicode MS;'>	
        <tr align='center'>
            <td rowspan='2'>Searched</td>
            <td rowspan='2'>Name</td>
            <td rowspan='2'>ID</td>
            <td colspan='3'># of Data Objects</td>
        </tr>
        <tr align='center'>
            <td>Text</td>
            <td>Image</td>
            <td>Total</td>            
        </tr>
        ";
    
    $sciname="";
    
    $color="white";
    foreach($arr as $row)
    {
        
        if($sciname <> $row["orig_sciname"]) 
        {            
            $sciname = $row["orig_sciname"];
            if($color=="white")$color="aqua";
            else               $color="white";
        }



        print"
        <tr bgcolor='$color'>
            <td >"               . utf8_decode($row["orig_sciname"]) . "</td>
            <td >"               . utf8_decode($row["sciname"]) . "</td>
            <td align='center'><a target='_eol' href='http://www.eol.org/pages/" . $row["tc_id"] . "'>" . $row["tc_id"] . "</a></td>            
            <td align='right'>"  . $row["text"] . "</td>
            <td align='right'>"  . $row["image"] . "</td>
            <td align='right'>"  . $row["total_objects"] . "</td>
        </tr>";        


    }    
    print"</table>";
}
function sort_deatils($arr_details)
{
    usort($arr_details, "cmp");
    while (list($key, $value) = each($arr_details)) 
    {
        //print "\$arr_details[$key]: " . $value["text"] . "<br>";//debug
    }
    
    //start limit number of returns
    global $returns;
    //print"<hr>returns=$returns " . count($arr_details) . "<hr>";////debug
    $array_count = count($arr_details);
    if($returns > 0)
    {
        for ($i = 0; $i < $array_count; $i++) 
        {
            if($i > $returns-1)
            {
                //print "del $i -- ";//debug
                unset($arr_details[$i]);
            }
        }     
    }
    
    return $arr_details;
}
function cmp($a, $b)
{
    global $sort_order;
    //return strcmp($a["text"], $b["text"]);    
    return $a["$sort_order"] < $b["$sort_order"];
}
function get_details($xml,$orig_sciname)
{
    $arr=array();
    foreach($xml->entry as $species)
    {
        //print "$species->title $species->id<br>";//debug
        $arr_do = get_objects_info("$species->id","$species->title","$orig_sciname");        
        $arr[]=$arr_do;
    }            
    return $arr;
}
function get_objects_info($id,$sciname,$orig_sciname)
{
    global $api_put_taxid_1;    
    global $api_put_taxid_2;    
    global $sciname_4color;

    //if(substr($id,0,4)=="http") $file = $id . $api_put_taxid_2;
    //else                        
    
    //id = http://www.eol.org/pages/2788733
    $id=str_ireplace("http://www.eol.org/pages/","",$id);
    $file = $api_put_taxid_1 . $id . $api_put_taxid_2;

    $xml = Functions::get_hashed_response($file);    
    //print"<hr>$file<hr>";
    //print"<hr>$api_put_taxid_1<hr>id = $id<hr>$api_put_taxid_2<hr>[[$file]] $xml<hr>";
       
    $text=0;$image=0;
    foreach($xml->taxon->dataObject as $object)
    {
            if      ($object->dataType == "http://purl.org/dc/dcmitype/StillImage") $image++;
            elseif  ($object->dataType == "http://purl.org/dc/dcmitype/Text") $text++;        
    }
    $total_objects=$image + $text;
    //print "$text $image<br>";//debug
    
    if($orig_sciname != $sciname_4color)
    {
        $sciname_4color=$sciname;
    }

    
    return array("tc_id"=>$id,"sciname"=>$sciname,"text"=>$text,"image"=>$image,"total_objects"=>$total_objects,"orig_sciname"=>$orig_sciname);
}

function get_val_var($v)
{
	if 		(isset($_GET["$v"])){$var=$_GET["$v"];}
	elseif 	(isset($_POST["$v"])){$var=$_POST["$v"];}
	
	if(isset($var))
	{
		return $var;
	}
	else	
	{
		return NULL;
	}	
}
function array_trim($a,$len) 
{ 	
	$b=array();
	$j = 0; 
	//print "<hr> -- "; print count($a); print "<hr> -- ";
	for ($i = 0; $i < $len; $i++) 
	{ 
		if (array_key_exists($i,$a))
		{
			if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; } 		
		}
	} 	
	return $b; 
}
//end



?>


