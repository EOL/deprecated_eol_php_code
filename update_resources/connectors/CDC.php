<?php
/* http://phil.cdc.gov/phil/home.asp */

$url = 'http://phil.cdc.gov/phil/details.asp';  

$arr_id_list = get_id_list();

//start to activate session
$philid = 11705;
list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) = process($url,$philid);
//end


    for ($i = 0; $i < count($arr_id_list); $i++) 
    {
        print "$i . " . $arr_id_list[$i] . "<br>";
        $philid = $arr_id_list[$i];        
        list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) = process($url,$philid);
        print"$id<hr>$image_url<hr>
        $description<hr>
        $desc_pic<hr>
        $desc_taxa<hr>
        $categories<hr>
        $taxa<hr>
        $copyright<hr>
        $providers<hr>
        $creation_date<hr>
        $photo_credit<hr>
        $outlinks";
        print"<hr><hr>";
    }




function get_id_list()
{
    $id_list = array();    
    for ($i=1; $i <= 7; $i++)//we only have 7 html pages with the ids, the rest of the pages is not server accessible.
    {
        $url = "http://127.0.0.1/cdc/id_list_00" . $i . ".htm";
        $handle = fopen($url, "r");	
        if ($handle)
        {
            $contents = '';
        	while (!feof($handle)){$contents .= fread($handle, 8192);}
        	fclose($handle);	
        	$str = $contents;
        }
    
	    $beg='<tr><td><font face="arial" size="2">ID#:'; $end1="</font><hr></td></tr>"; $end2="173"; $end3="173";			
    	$arr = parse_html($str,$beg,$end1,$end2,$end3,$end3,"all");	//str = the html block
        
        print count($arr) . "<br>";
    
        $id_list = array_merge($id_list, $arr);
    
        //print_r($id); print"<hr>";
    }
    
    print "total = " . count($id_list) . "<hr>"; //exit;

    return $id_list;
}
    


function process($url,$philid)
{
    $contents = cURL_it($philid,$url);
    if($contents) print "";
    else exit("bad post");
    
    /*
    list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) 
    = parse_contents($contents);
       
    */
    
    $arr = parse_contents($contents);
    return $arr;    
    
}





exit("<hr>-done-");


function parse_contents($str)
{

    /*
    $url = "http://127.0.0.1/cdc/cdc1.htm";
    $url = "http://127.0.0.1/cdc/cdc2.htm";
    $url = "http://127.0.0.1/cdc/cdc3.htm";
    $handle = fopen($url, "r");	
    if ($handle)
    {
        $contents = '';
    	while (!feof($handle)){$contents .= fread($handle, 8192);}
    	fclose($handle);	
    	$str = $contents;
    }
    */

    //========================================================================================
	$beg="ID#:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$id=$arx;
	//print "<hr>id = " . $id;	print "<hr>";
    $image_url = "http://phil.cdc.gov/PHIL_Images/" . $id . "/" . $id . "_lores.jpg";
    //print"<img src='http://phil.cdc.gov/PHIL_Images/" . $id . "/" . $id . "_lores.jpg'><hr>";    
    
    
	//========================================================================================
	$beg="<td><b>Description:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$description=trim($arx);    
	//print $description;	print "<hr>"; //exit;    

	//========================================================================================
    $description = "xxx" . $description;
	$beg="xxx<b>"; $end1="</b><p>"; $end2="173"; $end3="173";			
	$arx = parse_html($description,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$desc_pic=$arx;    
	//print "desc_pic<br>" . $desc_pic;	print "<hr>"; //exit;
    
    
    

    $description = str_ireplace('xxx', '', $description);        
    $desc_taxa = str_ireplace($desc_pic, '', $description);        
    //print "desc_taxa<br>" . $desc_taxa;	print "<hr>"; //exit;
    
        
    
    //========================================================================================
	//$beg='<table border="0" cellpadding="0" cellspacing="0"><tbody><tr><td>CDC Organization</td></tr></tbody></table>'; 
    $beg='<table border="0" cellpadding="0" cellspacing="0"><tr><td>CDC Organization</td></tr></table>';      

	$end1='<tr bgcolor="white" valign="top"><td><b>Copyright Restrictions:</b>'; 
	$end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$categories=$arx;

	$tmp=trim($categories);
	$tmp = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $tmp);	
	$tmp = substr($tmp,0,strlen($tmp)-10);
    
    
    $tmp = str_ireplace('<!--<td>&nbsp;&nbsp;</td>-->', '', $tmp);    
    
    $tmp = strip_tags($tmp,"<td><tr><table><img>");
    
	$categories = $tmp;
	//print $categories;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	

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
	$end1='</a></b></td>'; 
	$end2="</a></td>"; $end3="173";			
	$arx = parse_html($str_stripped,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$arx = trim($arx);
	$arx = substr($arx,2,strlen($arx));
    $taxa = $arx;
	//print "taxa = [$taxa] <hr>";
    
    
    
	//========================================================================================
	$beg="Copyright Restrictions:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$copyright=$arx;
	//print $copyright;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	
	$beg="Content Providers(s):</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$providers=$arx;
	//print $providers;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	
	$beg="Creation Date:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$creation_date=$arx;
	//print $creation_date;	print "<hr>"; //exit;
    
        
    //========================================================================================	
	$beg="Photo Credit:</b></td><td>"; $end1="</td></tr>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$photo_credit=$arx;
	//print $photo_credit;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	
	$beg='Links:</b></td><td><table><tbody><tr valign="top"><td><li></li></td><td>'; 
    $end1="</td></tr></tbody></table></td>"; $end2="173"; $end3="173";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$outlinks=$arx;    
    $outlinks = str_ireplace('</td></tr></tbody></table><table><tbody><tr valign="top"><td><li></li></td><td>', '<br>', $outlinks);
	//print $outlinks;	print "<hr>"; //exit;
        
    
    //========================================================================================	
	
    
    
    return array ($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks);
    

}//function parse_contents($contents)


function cURL_it($philid,$url)
{
    
    $fields = 'philid=' . $philid;
  
    $ch = curl_init();  
    curl_setopt($ch,CURLOPT_URL,$url);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // not to display the post submission
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    curl_setopt($ch,CURLOPT_POST, $fields);  
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);  
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);  
    $output = curl_exec($ch);
    $info = curl_getinfo($ch); 

    /*
    src="images/    
    http://phil.cdc.gov/phil/images/nodedownline.gif
    */
    
    $output = str_ireplace('src="images/', 'src="http://phil.cdc.gov/phil/images/', $output);
    
    //print $output; exit;
    
    curl_close($ch);

    $ans = stripos($output,"The page cannot be found");
    $ans = strval($ans);
    if($ans != "")  return false;
    else            return $output;    
    
}//function cURL_it($philid)

	
function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL)	//str = the html block
{
    //PRINT "[$all]"; exit;

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


    if($all == "")	
    {
        $id='';
	    for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}		
        return $id;
    }
    elseif($all == "all") return $arr;
	
}//end function
	

?>

