<?php

$arr_rem = array();	//to be removed from list, URL not responding
$arr_rem_i = 0;


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


/*

http://www.ubio.org/webservices/service.php?function=findIT&url=http://spire.umbc.edu/ontologies/EthanPlants.owl

http://spire.umbc.edu/ontologies/EthanPlants.owl

http://zipcodezoo.com/Protozoa/L/Lepocinclis_ovata/
*/


$list 			= get_val_var('list');

$separator 			= get_val_var('separator');
$choice 			= get_val_var('choice');
$withCSV 			= get_val_var('withCSV');

//exit("$withCSV");


if($choice==""){
print"<i>Please paste your list of URLs inside the box. <br>Select a filter and separator then click 'Submit'.
<p>
The URLs will be sent to UBio-FindIT, and this tool will output a tab-delimited TXT file of all names gathered by FindIT 
using the URLs submitted.
<p>
It is recommended to use a Spreadsheet in opening the tab-delimited TXT file.
</i>
";
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
//exit;
print"<table cellpadding='3' cellspacing='0' border='1' style='font-size : small; font-family : Arial Unicode MS;'>";

$us = "&#153;";	//unique separator


//////////////////////////////////////////////
//FindIT variables:
$FindIT_header = array('Name string','Canonical','NameBank ID');

$FindIT_elements	= array(	
							'nameString',
							'namebankID'
						   );
$FindIT_elements_withAttrib	= array('parsedName');
$PARSEDNAME_attrib = array('CANONICAL');
$field_before_cr = "ENTITY"; //field before carriage return

for ($i = 0; $i < count($FindIT_header); $i++) 
{ 
	$rd .= $FindIT_header[$i] . $sep;
} 
$rd .= "\n";


//////////////////////////////////////////////



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



for ($i = 0; $i < count($arr); $i++) 
{


	// /* list of entries
	
	$filename = $FindIT . trim($arr[$i]);
	
	print"<tr><td>$i. <a target='$i' href='$filename'>$arr[$i]</a></td></tr>";

	$cont="y";
	if(!is_well_formed($filename)){print"<tr><td>-not well formed-</td></tr>";$cont="n";}

	if($cont == "y")	
	{
	
	
	//print"<tr><td>"; working well
	
	
	
//start proc
//##############################################################################################
//##############################################################################################
//start read data
//##############################################################################################
//##############################################################################################


if (! ($xmlparser = xml_parser_create()) )
{ 
	die ("Cannot proceed. Cannot create parser");
}

$current="";
//function start_tag($parser, $name, $attribs) 

//function end_tag($parser, $name) {


xml_set_element_handler($xmlparser, "start_tag", "end_tag");


//function tag_contents($parser, $data) 


xml_set_character_data_handler($xmlparser, "tag_contents");

//$filename = "sample.xml";
//$filename = "http://www.ubio.org/webservices/service.php?function=findIT&url=http://zipcodezoo.com/Protozoa/L/Lepocinclis_ovata/";

$arr[$i] = trim($arr[$i]);
$filename = $FindIT . $arr[$i];


if (!($fp = fopen($filename, "r"))) 
{ 
	//die("cannot open ".$filename); 
	//print "<hr> Problematic URL, please remove from list: " . $arr[$i] . " <hr>";	
	$arr_rem[$arr_rem_i] = "<a target='_blank' href='$arr[$i]'>$arr[$i]</a>";
	$arr_rem_i++;	
}
else
{
	while ($data = fread($fp, 4096))
	{
	   $data=eregi_replace(">"."[[:space:]]+"."<","><",$data);
	   if (!xml_parse($xmlparser, $data, feof($fp))) 
	   {
	   		$reason = xml_error_string(xml_get_error_code($xmlparser));
	      	$reason .= xml_get_current_line_number($xmlparser);
	      	//die($reason);
			exit("PHP XML parsing error. System will terminate. $reason");
			
	   }
	}
	xml_parser_free($xmlparser);
}





//##############################################################################################
//##############################################################################################
//end read data
//##############################################################################################
//##############################################################################################
//end proc

	print "<tr><td>names = $sub_total</td></tr>"; $sub_total=0;
	
	//print"</td></tr>"; working well
	// */

	}//if($cont == "y")	
}


print "</table>";


if(count($arr_rem) != 0)
{
	print"<hr><font color='red'>Too-long to respond URLs, please remove from list:</font><br>";
	list_array($arr_rem);
}


?>



<?php





//if($withCSV=='on')
if(1 == 1)
{
	$fileidx = time();
	
	//$filename ="temp/" . $fileidx . ".csv"; 
	$filename ="temp/" . $fileidx . ".txt"; 
	
	$fp = fopen($filename,"a"); // $fp is now the file pointer to file $filename
	if($fp)
	{
		fwrite($fp,$rd);    //    Write information to the file
	    fclose($fp);        //    Close the file

	    print "<hr><i>Use a spreadsheet to open the tab-delimited TXT file created for ";
		print "<a target='_blank' href=$filename> - Download - </a>
		</i>
		";

		/*
		total no. of names compiled = $total_cnt
		print "
		<br><br>Right click on the 'Download' link above and 'save target as'";
		*/
	} 
	else 
	{
		print "Error saving file!";
	}
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


//start remove array entry if blank
/** * Trims an array from empty elements. * * @param $a the array to trim. * @return a new array with the empty elements removed. 
*/ 
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



function get_attribute_value($main,$fld)
{
	if ($main->hasAttribute($fld))
	{	
		$temp = $main->getAttributeNode($fld); 
		return $temp->nodeValue;
	}
	else
	{
		//return "$fld attribute does not exist.";
		return null;
	}	
}

function get_element_value($main,$fld)
{
	$temp = $main->getElementsByTagName( $fld ); 
	if(is_object($temp->item(0)))
	{
		return $temp->item(0)->nodeValue;
		//return is_resource($temp->item(0)) . is_object($temp->item(0));
	}
	else
	{
		//return "$fld element does not exist.";
		return null;
	}
}

//////////////////////////////////////////////// start of function for url_lookup --- xml parsing...
function start_tag($parser, $name, $attribs) 
{
	global $current;
	global $FindIT_elements_withAttrib;
	global $PARSEDNAME_attrib;
	global $rd;
	global $sep;
	global $total_cnt, $sub_total;
	
	$current = $name;
	
   //print "Current tag : ".$name."<br />";
   if (is_array($attribs)) {
      //print "Attributes : <br />";
      while(list($key,$val) = each($attribs)) 
	  {
         $attrib = "$key";
		 $value = "$val";
		 //print "Attribute ".$key." has value ".$val."<br />";
		 
		for ($k = 0; $k < count($FindIT_elements_withAttrib); $k++) 
		{
			$fld = strtoupper($FindIT_elements_withAttrib[$k]);
			if($fld == $current)
			{ 
				
				//print " element with attrib $fld -- "; 

				if($fld == "PARSEDNAME"){$arr = $PARSEDNAME_attrib;}
				
				
				for ($j = 0; $j < count($arr); $j++) 
				{
					$att = $arr[$j];
					if($att == $attrib)
					{	//print "$value --- "; 
						$rd .= $value . $sep;
						$total_cnt++;
						$sub_total++;

						
					}					
				}												
			}		
			
		}			 
		 
		 
       }
    }
}
function end_tag($parser, $name) {
	Global $field_before_cr;
	Global $rd;
	
   //print "Reached ending tag ".$name."<br /><br />";
   if($name == $field_before_cr)
   	{	//print "<br>"; 
   		$rd .= "\n";
	}
}
function tag_contents($parser, $data) 
{
	Global $current;
	Global $FindIT_elements;
	Global $rd;
	Global $sep;
	global $total_cnt,$sub_total;
	
			
	//print "Contents : ".$data."<br />";
	
	//print count($FindIT_elements) . " " ;
	
	for ($i = 0; $i < count($FindIT_elements); $i++) 
	{
		$fld = strtoupper($FindIT_elements[$i]);
		if($fld == $current)
		{ 
			//print "$data --- "; 
			//$data = str_replace(",", "&#44;", $data);
			$rd .= $data . $sep;
			/*
			$total_cnt++;
			$sub_total++;
			*/
			
		}
	}							
	//print "[$total_cnt] ";
	
	/*	
	if ($current == "FILENAME") { echo $data; }
	if ($current == "NAME") { echo $data; }	
   	*/
	
}


function list_array($arr)
{
	print"<table>";
	for ($i = 0; $i < count($arr); $i++)
	{		
		$arr[$i] = str_ireplace("'","",$arr[$i]);			
		print"<tr><td><i>$arr[$i]</i></td></tr>";
	}	
	print"</table>";
}

?>


