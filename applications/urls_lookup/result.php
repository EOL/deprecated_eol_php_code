<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");

//ini_set('display_errors',1);
//error_reporting(E_ALL | ~E_NOTICE);
//ini_set('display_errors',1);
//error_reporting(E_ALL);

error_reporting(0);


$arr_4saving=array();

/*
 The term vetted refers to vetted='trusted' or = 5
 
 published to published=1
 
 and visible to visibility_id=visible or = 1

 vetted='unknown' is commonly referred to as unvetted
 
 and there is also vetted='untrusted' which means it was curated and deemed to be removed from the site
 those items will likely get visibility_id=Invisible
 
 its confusing as there are a lot of fields to consider
 we have a diagram that is probably making its way into documentation today

*/


$total_cnt=0;
$sub_total=0;
$eol_site = "www.eol.org";
$eol_site = "app1.eol.org";
$FindIT="http://www.ubio.org/webservices/service.php?function=findIT&url=";
$nameBankURL="http://www.ubio.org/browser/details.php?namebankID=";

/*
http://www.ubio.org/webservices/service.php?function=findIT&url=http://spire.umbc.edu/ontologies/EthanPlants.owl
http://spire.umbc.edu/ontologies/EthanPlants.owl
http://zipcodezoo.com/Protozoa/L/Lepocinclis_ovata/
*/

$list 			= get_val_var('list');
$separator 		= get_val_var('separator');
$choice 		= get_val_var('choice');
$withCSV 		= get_val_var('withCSV');

if($choice=="")
{
    print"<i>Please paste your list of URLs inside the box. <br>Select a filter and separator then click 'Submit'.
    <p>The URLs will be sent to UBio-FindIT, and this tool will output a tab-delimited TXT file of all names gathered by FindIT 
    using the URLs submitted.<p>It is recommended to use a Spreadsheet in opening the tab-delimited TXT file.</i>";
    exit;
}

$rd	= "";	//row data
$cr 	= "\n";

$sep = ",";
$sep = chr(9);		//tab


//exit($choice);
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
	
//print $list;

$arr = explode("$separator", $list);
$arr=array_trim($arr); 

print "<font size='2' face='courier'>Total no. of URLs submitted: " . " " . count($arr) . "</font><hr>";

$us = "&#153;";	//unique separator
$rd .= "\n";


print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";

for ($i = 0; $i < count($arr); $i++) 
{
	$url = $FindIT . urlencode(trim($arr[$i]));	
    
    $y=$i+1;
	print"
    <tr><td>$y. <a href='$url'>$arr[$i]</a></td></tr>    
    ";

	$cont="y";

    
    if($xml = Functions::get_hashed_response($url))
    {        
        $names = get_names($xml);                
        $html=$names[0];
        $sub_total = $names[1];
        $total_cnt += $sub_total;
        print"<tr><td>$html</td></tr>";        
    }
    else
    {
        print"<tr><td>-not well formed-</td></tr>";
    }

    print "<tr><td>names = $sub_total</td></tr>";  	
    $sub_total=0;	

}

print"<tr><td>total = $total_cnt</td></tr>"; 
print"</table>";


save_to_txt($arr_4saving);



//#################################################################################################
function save_to_txt($arr)
{
	$str="";        
	foreach($arr as $r)
	{
        $str .= $r["name"] . "\t" . $r["url"] . "\n";        
	}
    //to remove last char - for field separator
    
    //$str = substr($str,0,strlen($str)-1);
	//$str .= "\n";
    
    $fileidx = time();  
    $filename ="temp/" . $fileidx . ".txt"; 
	if($fp = fopen($filename,"a")){fwrite($fp,$str);fclose($fp);}		

    print "<hr><i>Use a spreadsheet to open the tab-delimited TXT file created for ";
	print "<a target='tab_delimited' href=$filename> - Download - </a></i>";
    
}//function save_to_txt

function get_names($xml)
{
    global $nameBankURL;
    global $arr_4saving;
       
    $arr=array();
    $html="<table cellpadding='3' cellspacing='0' border='0' style='font-size : small; font-family : Arial Unicode MS;'>";    
    
    $i=0;
    foreach($xml->allNames->entity as $name)
    {        
        if($name->score >= 0.99 and isset($name->namebankID))
        {
            $i++;
            $url = $nameBankURL . $name->namebankID;
            $html .= "
            <tr><td><a href='$url' target='namebank'>$name->nameString</a></td></tr>
            ";                   
               
            $arr_4saving[]=array("name"=>$name->nameString,
                                 "url"=>$url
                                );
            
        }
    }
    $html.="</table>";
    
    return array($html,$i);
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
function array_trim($a) 
{ 	
	$b=array();
	$j = 0; 
	for ($i = 0; $i < count($a); $i++) 
	{ 
		if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; } 
	} 
	return $b; 
}
//end









function is_well_formed($xmlfile)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $xmlfile);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($curl);
	curl_close($curl);
	if (simplexml_load_string($output)) {return true;} 
	else 								{return false;}
}
?>


