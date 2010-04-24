<?php

require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$eol_site = "www.eol.org";

$report = 'list';	//original functionality

$returns 	= get_val_var('return');
$sort_order = get_val_var('sort');
$vetted     = get_val_var('vetted');

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

$api_put_species="http://labs1.eol.org/api/search/";
$api_put_taxid_1="http://labs1.eol.org/api/pages/";
$api_put_taxid_2="?images=75&text=75&subjects=all&vetted=$vetted";    
//$api_put_taxid_2="?images=75&text=75&subjects=all";    

$arr_table=array();
foreach($arr as $sciname)
{
	//print"$sciname<br>";//debug
    $file = $api_put_species . urlencode($sciname);
    //print"$file --- ";
    $xml = Functions::get_hashed_response($file);
    $arr_details = get_details($xml);
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
        <tr>
            <td>Name</td>
            <td>ID</td>
            <td># of Text</td>
            <td># of Images</td>
            <td>Total</td>            
        </tr>";

    foreach($arr as $row)
    {
        print"
        <tr>
            <td >"               . $row["sciname"] . "</td>
            <td align='center'>" . $row["tc_id"] . "</td>
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
function get_details($xml)
{
    $arr=array();
    foreach($xml->entry as $species)
    {
        //print "$species->title $species->id<br>";//debug
        $arr_do = get_objects_info("$species->id","$species->title");        
        $arr[]=$arr_do;
    }            
    return $arr;
}
function get_objects_info($id,$sciname)
{
    global $api_put_taxid_1;    
    global $api_put_taxid_2;    
    
    $file = $api_put_taxid_1 . $id . $api_put_taxid_2;
    $xml = Functions::get_hashed_response($file);    
   
    $text=0;$image=0;
    foreach($xml->taxon->dataObject as $object)
    {
            if      ($object->dataType == "http://purl.org/dc/dcmitype/StillImage") $image++;
            elseif  ($object->dataType == "http://purl.org/dc/dcmitype/Text") $text++;        
    }
    $total_objects=$image + $text;
    //print "$text $image<br>";//debug
    return array("tc_id"=>$id,"sciname"=>$sciname,"text"=>$text,"image"=>$image,"total_objects"=>$total_objects);
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


