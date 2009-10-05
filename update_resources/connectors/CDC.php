<?php
//#!/usr/local/bin/php  
/*
connector for Public Health Image Library (CDC) 
http://phil.cdc.gov/phil/home.asp
*/

exit;
define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", false);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

//only on local; to be deleted before going into production
 /*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
exit;
 */

$resource = new Resource(79);
print "resource id = " . $resource->id . "<hr>";
//exit;

$schema_taxa = array();
$used_taxa = array();

$id_list=array();

$wrap = "\n";
//$wrap = "<br>";


$total_taxid_count = 0;
$do_count = 0;//weird but needed here


$url = 'http://phil.cdc.gov/phil/details.asp';  
$home_url = "http://phil.cdc.gov/phil/home.asp";
$arr_id_list = get_id_list();

$arr_desc_taxa = array();
$arr_categories = array();
$arr_outlinks = array();
                

//start to activate session
$philid = 11705;
list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) = process($url,$philid);
//end to activate session


for ($i = 0; $i < count($arr_id_list); $i++) 
{
    //main loop
    
    print "$i . " . $arr_id_list[$i] . "<br>";
    $philid = $arr_id_list[$i];        
    list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) = process($url,$philid);

    if(trim($taxa) == "")exit(" $philid blank taxa exists");
    
    /*
    print"$id<hr> ---
    $image_url<hr> ---
    $description<hr> ---
    $desc_pic<hr> ---
    $desc_taxa<hr> ---
    $categories<hr> ---
    $taxa<hr> ---
    $copyright<hr>
    $providers<hr> ---
    $creation_date<hr> ---
    $photo_credit<hr> ---
    $outlinks<hr> ---
    ";
    */
    
    $desc_taxa = str_ireplace("animals sre filtered", "animals are filtered", $desc_taxa);
    
    
    
    //$categories="xxx";
    $outlinks = utf8_encode($outlinks);
    $desc_pic = utf8_encode($desc_pic);
    $desc_taxa = utf8_encode($desc_taxa);
    
    $desc_pic = $desc_pic . "<br>" . "Created: $creation_date";
    
  

    if(in_array($taxa . $desc_taxa, $arr_desc_taxa))   $desc_taxa="";
    else                                       $arr_desc_taxa[] = $taxa . $desc_taxa;     

    if(in_array($taxa . $categories, $arr_categories)) $categories="";
    else                                       $arr_categories[] = $taxa . $categories;     
    
    if(in_array($taxa . $outlinks, $arr_outlinks))     $outlinks="";
    else                                       $arr_outlinks[] = $taxa . $outlinks;     


    if($categories != "")$desc_taxa .= "<hr>Categories:<br>$categories";   
    if($outlinks != "")  $desc_taxa .= "<hr>Outlinks:<br>$outlinks";

    
    //print"<hr><hr>";    
    print"<hr>";    
    
 

    $taxon = str_replace(" ", "_", $taxa);
    if(@$used_taxa[$taxon])
    {
        $taxon_parameters = $used_taxa[$taxon];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxa; //$main->taxid;
        $taxon_parameters["scientificName"]= $taxa;
        $taxon_parameters["source"] = $home_url;
        $used_taxa[$taxon] = $taxon_parameters;            
    }


    if(1==1)
    {
        if($do_count == 0)//echo "$wrap$wrap phylum = " . $taxa . "$wrap";

        $dc_source = $home_url;       

        $do_count++;        
        $agent_name = $photo_credit;
        $agent_role = "photographer";            
        $data_object_parameters = get_data_object("image",$taxon,$do_count,$dc_source,$agent_name,$agent_role,$desc_pic,$copyright,$image_url);               
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                 
        
        
        if($desc_taxa != "")
        {
        $do_count++;
        $agent_name = $providers;
        $agent_role = "source";            
        $data_object_parameters = get_data_object("text",$taxon,$do_count,$dc_source,$agent_name,$agent_role,$desc_taxa,$copyright,$image_url);                           
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);         
        }
        
        $used_taxa[$taxon] = $taxon_parameters;

    }//with photos
    
    
    
    
    //end main loop   
}

foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

echo "$wrap$wrap Done processing.";

function get_data_object($type,$taxon,$do_count,$dc_source,$agent_name,$agent_role,$description,$copyright,$image_url)   
{
        
        //$description = "<![CDATA[ $description ]]>";

        $dataObjectParameters = array();
        
        if($type == "text")
        {            
            //$dataObjectParameters["title"] = $title;
            
            //start subject        
            $dataObjectParameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
            //end subject
            
            
            $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";
            $dataObjectParameters["mimeType"] = "text/html";
            $dataObjectParameters["source"] = $dc_source;
        }
        elseif($type == "image")
        {
            $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
            $dataObjectParameters["mimeType"] = "image/jpeg";            
            $dataObjectParameters["mediaURL"] = $image_url;
            $dataObjectParameters["rights"] = $copyright;
            $dc_source ="";
        }
        
        $dataObjectParameters["description"] = $description;
        //$dataObjectParameters["created"] = $created;
        //$dataObjectParameters["modified"] = $modified;            
        $dataObjectParameters["identifier"] = $taxon . "_" . $do_count;        
        $dataObjectParameters["rightsHolder"] = "Public Health Image Library";
        $dataObjectParameters["language"] = "en";
        $dataObjectParameters["license"] = "http://creativecommons.org/licenses/publicdomain/";        
        
        //==========================================================================================
        /* working...
        $agent = array(0 => array(     "role" => "photographer" , "homepage" => ""           , $photo_credit),
                       1 => array(     "role" => "project"      , "homepage" => $home_url    , "Public Health Image Library")
                      );    
        */
        
        if($agent_name != "")
        {
            $agent = array(0 => array( "role" => $agent_role , "homepage" => $dc_source , $agent_name) );    
            $agents = array();
            foreach($agent as $agent)
            {  
                $agentParameters = array();
                $agentParameters["role"]     = $agent["role"];
                $agentParameters["homepage"] = $agent["homepage"];
                $agentParameters["logoURL"]  = "";        
                $agentParameters["fullName"] = $agent[0];
                $agents[] = new SchemaAgent($agentParameters);
            }
            $dataObjectParameters["agents"] = $agents;    
        }
        //==========================================================================================
        $audience = array(  0 => array(     "Expert users"),
                            1 => array(     "General public")
                         );        
        $audiences = array();
        foreach($audience as $audience)
        {  
            $audienceParameters = array();
            $audienceParameters["label"]    = $audience[0];
            $audiences[] = new SchemaAudience($audienceParameters);
        }
        $dataObjectParameters["audiences"] = $audiences;    
        //==========================================================================================
        
        return $dataObjectParameters;
}

function get_id_list()
{
    $id_list = array();    
    for ($i=1; $i <= 7; $i++)//we only have 7 html pages with the ids, the rest of the pages is not server accessible.
    {
        $url = "http://128.128.175.77/cdc/id_list_00" . $i . ".htm";
        $handle = fopen($url, "r");	
        if ($handle)
        {
            $contents = '';
        	while (!feof($handle)){$contents .= fread($handle, 8192);}
        	fclose($handle);	
        	$str = $contents;
        }    
	    $beg='<tr><td><font face="arial" size="2">ID#:'; $end1="</font><hr></td></tr>"; $end2="173xxx"; $end3="173xxx";			
    	$arr = parse_html($str,$beg,$end1,$end2,$end3,$end3,"all");	//str = the html block        
        print count($arr) . "<br>";    
        $id_list = array_merge($id_list, $arr);    
        //print_r($id); print"<hr>";
    }    
    print "total = " . count($id_list) . "<hr>"; //exit;
    $count_bef_unset = count($id_list);
    
    //start exclude ids that are images of dogs and their masters
    for ($i = 0; $i < count($id_list); $i++) 
    {
        if  (   $id_list[$i] == 11357   or
                $id_list[$i] == 11329   or
                $id_list[$i] == 10927   or
                $id_list[$i] == 10926   or
                $id_list[$i] == 10925                   
            )unset($id_list[$i]);
    }        
    //end exclude ids    

    print "<hr>count after unset = " . count($id_list);    
    $id_list = array_trim($id_list,$count_bef_unset);
    print "<hr>final count = " . count($id_list) . "<br>";    
    //exit("<hr>stopx");    
    return $id_list;
    
}

function process($url,$philid)
{
    $contents = cURL_it($philid,$url);
    if($contents) print "";
    else print exit("<hr>bad post [$philid]<hr>");
    
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
    //global $arr_desc_taxa;
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
	$beg="ID#:</b></td><td>"; $end1="</td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$id=$arx;
	//print "<hr>id = " . $id;	print "<hr>";
    $image_url = "http://phil.cdc.gov/PHIL_Images/" . $id . "/" . $id . "_lores.jpg";
    //print"<img src='http://phil.cdc.gov/PHIL_Images/" . $id . "/" . $id . "_lores.jpg'><hr>";    
    
    
	//========================================================================================
	$beg="<td><b>Description:</b></td><td>"; $end1="</td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$description=trim($arx);    
	//print $description;	print "<hr>"; //exit;    

	//========================================================================================
    $description = "xxx" . $description;
	$beg="xxx<b>"; $end1="</b><p>"; $end2="173xxx"; $end3="173xxx";			
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
	$end2="173xxx"; $end3="173xxx";			
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
	$end2="173xxx"; $end3="173xxx";			
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
	print "taxa = [$taxa] <hr>";
    
    
    
	//========================================================================================
	$beg="Copyright Restrictions:</b></td><td>"; $end1="</td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$copyright=$arx;
	//print $copyright;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	
	$beg="Content Providers(s):</b></td><td>"; $end1="</td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$providers=$arx;
	//print $providers;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	
	$beg="Creation Date:</b></td><td>"; $end1="</td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$creation_date=$arx;
	//print $creation_date;	print "<hr>"; //exit;
    
        
    //========================================================================================	
	$beg="Photo Credit:</b></td><td>"; $end1="</td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$photo_credit=$arx;
	//print $photo_credit;	print "<hr>"; //exit;
    
    
    
    //========================================================================================	
	$beg='Links:</b></td><td><table><tr valign="top"><td><li></li></td><td>';           
    $end1="</td></tr></table></td></tr>"; $end2="173xxx"; $end3="173xxx";			
	$arx = parse_html($str,$beg,$end1,$end2,$end3,$end3);	//str = the html block
	$outlinks=$arx;    
    
    $outlinks = str_ireplace('</td></tr></table><table><tr valign="top"><td><li></li></td><td>', '<br>', $outlinks);
    //$outlinks = strip_tags($outlinks,"<a>"); //not needed
    
	//print "<hr>$str";
    //print "<hr>outlinks: " . $outlinks;	print "<hr>"; //exit;
        
    
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
	

function array_trim($a,$len) 
{ 	
	$b=array();
	$j = 0; 
	//print "<hr> -- "; print count($a); print "<hr> -- ";
	for ($i = 0; $i < $len; $i++) 
	{ 
		//if (array_key_exists($i,$a))
        if(isset($a[$i]))
		{
			if (trim($a[$i]) != "") { $b[$j++] = $a[$i]; } 		
            else print "[walang laman]";
		}
	} 	
	return $b; 
}

?>

