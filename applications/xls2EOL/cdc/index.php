<?php

require "func_parse_html.php";

$url = "http://127.0.0.1/cdc/cdc.html";
//$url = "http://127.0.0.1/cdc/cdc2.html";
	
print "<hr>$url<hr>";
		
$handle = fopen($url, "r");	
	
if ($handle)
{			
	$contents = '';
	while (!feof($handle)){$contents .= fread($handle, 8192);}
	fclose($handle);
	
	$str = $contents;

	$beg="ID#:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$id=$arx;
	print $id;	print "<hr>";
	
	$beg="<td><b>Description:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$description=$arx;
	print $description;	print "<hr>"; //exit;

	$beg='<table border="0" cellpadding="0" cellspacing="0"><tbody><tr><td>CDC Organization</td></tr></tbody></table>'; 
	$end1='<tr bgcolor="white" valign="top"><td><b>Copyright Restrictions:</b>'; 
	$end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$categories=$arx;

	$tmp=trim($categories);
	$tmp = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $tmp);	
	$tmp = substr($tmp,0,strlen($tmp)-10);
	$categories = $tmp;
	print $categories;	print "<hr>";
	
	//start 
	/*
	$str = 'aaa 1 yy aaa 2 yy aaa 3 yy aaa 4 yy';
	$beg='aaa'; 
	$end1='yy'; 
	$end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	print $arx;
	*/
	
	$str_stripped = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);	
	$beg="document.form2.creationdate.value = '1';"; 
	//$end1='</a></b></td></tr></tbody></table></td></tr></tbody></table>'; 
	$end1='</a></b></td>'; 
	$end2="</a></td>"; $end3="173";			
	$arx = parse_html($str_stripped,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$arx = trim($arx);
	$arx = substr($arx,2,strlen($arx));
	print "[$arx]";
	
		
	
	//end
	
	
}
	
	

?>

