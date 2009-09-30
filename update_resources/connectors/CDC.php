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
	
function parse_html($str,$beg,$end1,$end2,$end3,$end4)	//str = the html block
{

	$beg_len = strlen(trim($beg));
	$end1_len = strlen(trim($end1));
	$end2_len = strlen(trim($end2));
	$end3_len = strlen(trim($end3));	
	$end4_len = strlen(trim($end4));	
	
	//print "[[$str]]";

	$str = trim($str); 
	
	$str = $str . "|||";
	
	$len = strlen($str);
	
	$arr = array(); $k=0;
	
	for ($i = 0; $i < $len; $i++) 
	{
		if(substr($str,$i,$beg_len) == $beg)
		{	
			$i=$i+$beg_len;
			$pos1 = $i;
			
			//print substr($str,$i,10) . "<br>";									

			$cont = 'y';
			while($cont == 'y')
			{
				if(	substr($str,$i,$end1_len) == $end1 or 
					substr($str,$i,$end2_len) == $end2 or 
					substr($str,$i,$end3_len) == $end3 or 
					substr($str,$i,$end4_len) == $end4 or 
					substr($str,$i,3) == '|||' )
				{
					$pos2 = $i - 1; 					
					$cont = 'n';					
					$arr[$k] = substr($str,$pos1,$pos2-$pos1+1);															
					
					//print "$arr[$k] <hr>";
					
					$k++;
				}
				$i++;
			}//end while
			$i--;			
		}
		
	}//end outer loop
	
	for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}	
	
	return $id;

	
}//end function
	

?>

