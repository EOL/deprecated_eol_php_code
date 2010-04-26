<?php
/* connector for Photosynth -- http://photosynth.net/
estimated execution time:

Connector sends a post request to their unofficial service
*/

$timestart = microtime(1);

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$wrap = "\n";
$wrap = "<br>";

$resource = new Resource(1);
print "resource id = " . $resource->id . "$wrap"; //exit;

$schema_taxa = array();
$used_taxa = array();

$id_list=array();

$total_taxid_count = 0;
$do_count = 0;

$url        ="";
$home_url   ="http://photosynth.net/";
$form_url   ="http://photosynth.net/PhotosynthHandler.ashx";
$site_url   ="http://photosynth.net/";


$records = process($form_url);
print"<pre>";print_r($records);print"</pre>";
$taxa_list = get_taxa_list($records);
print"<pre>";print_r($taxa_list);print"</pre>";
//exit;


print("$wrap count taxa_list = " . count($taxa_list) );

//
//for ($i = 0; $i < 5; $i++) 
//for ($i = 0; $i < count($taxa_list); $i++) 
$i=0;
foreach($taxa_list as $taxa)
{    
    $i++;
    //main loop
    print "$wrap $wrap";
    print $i . " of " . count($taxa_list) . " id=" . $taxa["name"] . " ";

    $taxon          = $taxa["name"];
    $taxon_id       = $taxa["id"];
    $dc_source      = $taxa["source_url"];
    $classification = $taxa["classification"];
    
    if(trim($taxon) == "")
    {   
        print " --blank taxa--";
        continue; 
    }    
        
    if(@$used_taxa[$taxon])
    {
        $taxon_parameters = $used_taxa[$taxon];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = $taxon_id;
        
        $taxon_parameters["kingdom"]    = @$classification["kingdom"];
        $taxon_parameters["phylum"]     = @$classification["phylum"];
        $taxon_parameters["class"]      = @$classification["class"];
        $taxon_parameters["order"]      = @$classification["order"];
        $taxon_parameters["family"]     = @$classification["family"];
        $taxon_parameters["genus"]      = @$classification["genus"];

        $taxon_parameters["scientificName"]= $taxon;
        $taxon_parameters["source"] = $dc_source;
        
        /*
        $taxon_parameters["commonNames"] = array();
        foreach($arr_common_names as $commonname)
        {            
            if($commonname)
            {
                $commonname = "<![CDATA[$commonname]]>";
                $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
            }
        }                
        if(count($arr_references) > 0)
        {
            $references=array();
            $taxon_parameters["references"] = array();            
            foreach ($arr_references as $ref)
            {                        
                $referenceParameters = array();
                $href = get_href_from_anchor_tag($ref);
                $ref = get_str_from_anchor_tag($ref);
            
                if(substr($ref,0,19)=="Goffredo S., Radeti")$ref="Goffredo S., Radeti J., Airi V., and Zaccanti F., 2005";
                $referenceParameters["fullReference"] = trim($ref);                               
                if($href)$referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => $href));                                
                $references[] = new SchemaReference($referenceParameters);
            }
            $taxon_parameters["references"] = $references;
        }    
        */
                
        $used_taxa[$taxon] = $taxon_parameters;            
    }

    if(1==1)
    {
        $dc_source = $home_url;       
        
        //start images
        //foreach ($arr_images as $value)
        foreach ($records as $rec)
        {
            if($taxon == $rec["taxon"])
            {
                $do_count++;      
                $do_id      = $rec["do_id"];  
                $agent      = $rec["agent"];
                $title      = $rec["title"];
                $dc_source  = $rec["source_url"];
                $rightsHolder  = $rec["rightsHolder"];
                $description  = $rec["caption"];
                
                $data_object_parameters = get_data_object("photosynth",$taxon,$do_id,$agent,$title,$dc_source,$rightsHolder,$description);
                $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                                     
            }            
        }
        //end images
        
        /*
        //start skeletons         
        if($html_skeletons != "")
        {   $do_count++;
            $agent_name = ""; $agent_role = ""; $image_url=""; $copyright="";
            $title="Biology: Skeleton";            
            $dc_source = $url_for_skeletons;
            $subject="http://rs.tdwg.org/ontology/voc/SPMInfoItems#Biology";
            $data_object_parameters = get_data_object("text",$taxon,"skeleton",$dc_source,$agent_name,$agent_role,$html_skeletons,$copyright,$image_url,$title,$subject);
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                                 
        }        
        //end skeletons
        */
        
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

$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";

exit("$wrap$wrap Done processing.");


//######################################################################################################################
//######################################################################################################################
//######################################################################################################################
function get_taxa_list($records)
{
    $arr=array();
    foreach($records as $rec)
    {
        $arr[$rec["taxon"]]=array("id"=>$rec["taxon_id"],"name"=>$rec["taxon"],"classification"=>$rec["classification"],
        "source_url"=>$rec["source_url"]);
    }
    return $arr;
}

function img_href_src($str)
{
    /*
    <A HREF="imagedetail.cfm?imageid=5880&genus=Paranthosactis&species=denhartogi&subgenus=&subspecies=">
    <IMG SRC="images/05851_05900/05880.jpg" BORDER=0 HEIGHT=80 WIDTH=80></a>
    */
    
    $beg='<A HREF="'; $end1='">'; 
    $href = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    
    $beg='<IMG SRC="'; $end1='" BORDER='; 
    $src = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    
    return array($href,$src);
    
}

function get_data_object($type,$taxon,$do_id,$agent,$title,$dc_source,$rightsHolder,$description)
{        

    $dataObjectParameters = array();
        
    if($type == "text")
    {            
        $dataObjectParameters["title"] = $title;            

        //start subject        
        /*
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();        
        $subjectParameters["label"] = $subject;                
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        */
        //end subject            
            
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";
        $dataObjectParameters["mimeType"] = "text/html";
        $dataObjectParameters["source"] = $dc_source;
        
        $dataObjectParameters["identifier"] = $do_id;
        
    }
    elseif($type == "photosynth")
    {
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        //$dataObjectParameters["mimeType"] = "image/jpeg";            
        $dataObjectParameters["source"] = $dc_source;
        $dataObjectParameters["mediaURL"] = $dc_source;
        //$dataObjectParameters["rights"] = $copyright;
        $dataObjectParameters["identifier"] = $do_id;
    }
        
    $dataObjectParameters["description"] = $description;
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;            
    
    $dataObjectParameters["rightsHolder"] = $rightsHolder;
    $dataObjectParameters["language"] = "en";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/publicdomain/";        
    //$dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
    
    
        
    //==========================================================================================
    /* working...
    $agent = array(0 => array(     "role" => "photographer" , "homepage" => ""           , $photo_credit),
                   1 => array(     "role" => "project"      , "homepage" => $home_url    , "Public Health Image Library")
                  );    
    */
        
    if($agent)
    {
        $agents = array();
        foreach($agent as $agent)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";        
            $agentParameters["fullName"] = $agent["name"];
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


function process($url)
{
    global $wrap;
    $contents = cURL_it($url);
    if($contents) print "";
    else print "$wrap bad post $wrap ";
    $arr = parse_contents($contents);
    return $arr;        
}

function get_tabular_data($url,$item)
{   //return;

    global $wrap;
    
    //exit("<hr>$url");
    
    $table = Functions::get_remote_file($url);                
    
    if      ($item == "synonyms")                   $beg='<TH><B>Authorship</b></TH>'; 
    elseif  ($item == "references")                 $beg='<th align=left><b>Nomenclature Notes </b></td></th>'; 
    elseif  ($item == "skeletons")                  $beg='<th>Percent Magnesium</th>'; 
    elseif  ($item == "biological_associations")    $beg='<TH COLSPAN="2">Algal symbionts</TH>'; 
    elseif  ($item == "classification")             $beg='<th>Current Classification: Click on a taxon to view its components</th>'; 
    elseif  ($item == "common_names")               $beg='<TBODY>';     
    elseif  ($item == "nematocysts")                $beg='<th width="60">State</th>'; 
    //elseif  ($item == "specimens")                 $beg='<th>Source </th>'; 
    
    if    ($item == "classification")   $end1='</td>'; //$end1='<br> </td>'; //$end1='</tr>';//
    elseif($item == "common_names")     $end1='</TBODY>';
    else                                $end1='</table>'; 

    $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));                
    
    if($item == "classification" and $temp == "")    
    {
        $beg='<TH>Current classification</TH>';
        $end='</td>';
        $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));                
    }
    

    if( $item != "common_names" and
        $item != "specimens"
      ) $temp = substr($temp,5,strlen($temp));//to remove the '</tr>' at the start of the string    
        
    $temp = str_ireplace(array( '<tr class=listrow1 >',
                                '<tr class=listrow2 >',
                                '<tr  class=listrow2  >',
                                '<tr  class="listrow2"  >',
                                '<tr class="listrow1" >',
                                '<tr  class="listrow2"  >',
                                '<tr class="listrow1" >'), '<tr>', $temp);			                                 
                                
    $temp = str_ireplace('<TR class="common2">','<tr>',$temp);

    if($item == "specimens")// to fix the weird <tr> withouth ending </tr>
    {
        $temp = str_ireplace('<tr>','</tr><tr>',$temp);        
    }    
                                                           
    //print $temp; exit;
    
    $temp = str_ireplace('<tr>' , "", $temp);	
    $temp = trim(str_ireplace('</tr>' , "***", $temp));	
    
    if($item != "classification")    $temp = substr($temp,0,strlen($temp)-3);//remove last '***'
    
    $arr = explode("***", $temp);
    $arr_records=array();
    
    for ($i = 0; $i < count($arr); $i++) 
    {
        $str = $arr[$i];
        $str = str_ireplace('<td>' , "", $str);	
        $str = trim(str_ireplace('</td>' , "***", $str));	
        if($item != "classification") $str = substr($str,0,strlen($str)-3);//remove last '***'
        
        $str=strip_tags($str,"<a><b><B><br><BR>");
        
        //$str = htmlspecialchars_decode($str);
        
        $arr2 = explode("***", $str);    

        $arr_records[]=$arr2;
    }
    
    if($item == "skeletons")//to check if 2nd column from table is NO
    {
        //print"<hr>";
        $temp_arr = $arr_records[0];
        //print trim($temp_arr[1]);
        if(trim($temp_arr[1])=="NO")$arr_records=array();
    }
    //print"<pre>";print_r($arr_records);print"</pre>";     
    //if($item == "skeletons")exit("<hr>");
    
    return $arr_records;
}



function parse_contents($str)
{

    global $wrap;
    global $site_url;
    
    //ditox    
    $beg='"Collections":['; $end1=']}'; 
    $str = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');                

    $str = str_ireplace('{"Images":[],' , "&arr[]=", $str);	
    $arr=array(); parse_str($str);	    
    //print"<pre>";print_r($arr);print"</pre>";
    
    $temp_arr=array();
    foreach($arr as $temp)
    {
        $str = str_ireplace(',"' , '|"', $temp);	        
        
        $r = explode("|",$str);
        $temp_arr[]=$r;
        //print"<pre>";print_r($r);print"</pre>";        
    }
    
    //print"<pre>";print_r($temp_arr);print"</pre>";        

    $final_arr=array();    
    foreach($temp_arr as $arr)
    {
        $str="";
        foreach($arr as $r)
        {
            $fv = explode('":',$r);
            $field = $fv[0];
            $value = $fv[1];            
            
            $field = str_ireplace('"' , '', $field);	            
            $value = str_ireplace('},' , '', $value);	            
            $value = str_ireplace('}' , '', $value);	            
            
            //print"$field = $value $wrap";            
            
            if($str == "")  $str .= "'$field' => $value";
            else            $str .= " , '$field' => $value";
        }            
        eval("\$final_arr[] = array(" . $str . ");");		
    }
    
    print"<pre>";print_r($final_arr);print"</pre>";        

    $r=array();
    foreach($final_arr as $arr)
    {
        print $arr["Id"] . "$wrap";
        $source_url = "http://photosynth.net/edit.aspx?cid=" . $arr["Id"];
        
        $classification = get_classification($arr["Description"]);
        $sciname = $classification["scientificname"];
        
        $r[]=array  (   "taxon"          => $sciname,   
                        "taxon_id"       => $arr["OwnerUserGuid"] . "_" . str_ireplace(" ","_",$sciname),   
                        "classification" => $classification,
                        "do_id"          => $arr["Id"],
                        "source_url"     => $source_url,
                        "agent"          => array("role" => "compiler" , "homepage" => $source_url , "name" => $arr["OwnerFriendlyName"]),                        
                        "thumbnailURL"   => $arr["ThumbnailUrl"],
                        "caption"        => trim($arr["Name"] . ". " . $arr["Description"] . ". Image count: " . $arr["ImageCount"]),
                        "title"          => $arr["Name"],
                        "rightsHolder"   => $arr["OwnerFriendlyName"]
                    );
        
    }    
    
    //print"<pre>";print_r($r);print"</pre>";exit;
    return $r;
    
    /*    
    return array($taxa,$url_for_main_menu
                    ,$arr_classification,$arr_images
                    ,$html_skeletons,$url_for_skeletons
                    ,$html_biological_associations,$url_for_biological_associations
                    ,$arr_common_names
                    ,$arr_references
                    ,$html_nematocysts,$url_for_nematocysts
                );
                */
}//function parse_contents($contents)

function get_classification($str)
{


    /*
    * taxonomy:kingdom=* for example “ taxonomy: b inomial=Vanessa atalanta” 
    * taxonomy:phylum=* taxonomy:order=Lepidoptera 
    * taxonomy:class= * 
    * taxonomy:order=* 
    * taxonomy:family=*
    * taxonomy:genus=* 
    * taxonomy:species=* 
    * taxonomy:binomial=* [e.g. genus and species with a space between them, enclose in quotes when you type] 
    * taxonomy:trinomial=* [e.g. genus, species, and subspecies with spaces between them, enclose in quotes when you type]     
    */    

    $beg='taxonomy:kingdom='; $end1='"'; $kingdom = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    $beg='taxonomy:phylum='; $end1='"'; $phylum = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    $beg='taxonomy:class='; $end1='"'; $class = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    $beg='taxonomy:order='; $end1='"'; $order = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    $beg='taxonomy:family='; $end1='"'; $family = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    $beg='taxonomy:genus='; $end1='"'; $genus = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    
    $scientifcname="";        
    $beg='taxonomy:trinomial='; $end1='"'; $scientificname = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');
    if($scientifcname == "")
    {
        $beg='taxonomy:binomial='; $end1='"'; $scientificname = parse_html($str,$beg,$end1,$end1,$end1,$end1,'');       
    }
    
    $sciname="";
    if($kingdom!="")$sciname=$kingdom;
    if($phylum!="")$sciname=$phylum;
    if($class!="")$sciname=$class;
    if($order!="")$sciname=$order;
    if($family!="")$sciname=$family;
    if($genus!="")$sciname=$genus;
    if($scientificname!="")$sciname=$scientificname;
    
    $arr = array(   "kingdom"=>$kingdom,
                    "phylum"=>$phylum,
                    "class"=>$class,
                    "order"=>$order,
                    "family"=>$family,
                    "genus"=>$genus,
                    "scientificname"=>$sciname
                );
    
    //print"$str<pre>";print_r($arr);print"</pre>";exit;
    return $arr;    
}

function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false)	//str = the html block
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
        if(strtolower(substr($str,$i,$beg_len)) == strtolower($beg))
		{	
			$i=$i+$beg_len;
			$pos1 = $i;			
			//print substr($str,$i,10) . "<br>";									
			$cont = 'y';
			while($cont == 'y')
			{
				if(	strtolower(substr($str,$i,$end1_len)) == strtolower($end1) or 
					strtolower(substr($str,$i,$end2_len)) == strtolower($end2) or 
					strtolower(substr($str,$i,$end3_len)) == strtolower($end3) or 
					strtolower(substr($str,$i,$end4_len)) == strtolower($end4) or 
					substr($str,$i,3) == '|||' )
				{
					$pos2 = $i - 1; 					
					$cont = 'n';					
					$arr[$k] = substr($str,$pos1,$pos2-$pos1+1);																				
					//print "$arr[$k] $wrap";					                    
					$k++;
				}
				$i++;
			}//end while
			$i--;			
            
            //start exit on first occurrence of $beg
            if($exit_on_first_match)break;
            //end exit on first occurrence of $beg
            
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
// */


function arr2html($arr_data,$arr_fields,$url_for_main_menu)
{
    $html="<a target='hexacorallians' href='$url_for_main_menu'>More info</a><br><table style='font-size : small;' border='1' cellpadding='4' cellspacing='0'>";
    $html .="<tr>";   
    foreach ($arr_fields as $value)
    {
        $html .="<th>$value</th>";      
    }
    $html .="</tr>";       
    foreach ($arr_data as $value)
    {
        $html .="<tr>";   
        foreach ($value as $item)
        {   
            if(stripos($item, "href") != "" )
            {
                $beg='">'; $end1='</a>'; 
                $item = trim(parse_html($item,$beg,$end1,$end1,$end1,$end1,""));                                  
            }
            $html .="<td>$item</td>";
        };      
        $html .="</tr>";   
    }    
    $html .="</table>";   
    return $html;    
}

function get_str_from_anchor_tag($str)
{
    $beg='">'; $end1='</a>'; 
    $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));                                  
    
    //to get string after </a> if there are any
    $str .= "xxx";
    $beg='</a>'; $end1='xxx'; 
    $temp2 = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));                                     
    
    return $temp . " " . $temp2;
}
function get_href_from_anchor_tag($str)
{
    //      <a href="reference_detail.cfm?ref_number=58&type=Article"> 
    $beg='href="'; $end1='">'; 
    return trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",true));//exist on first match = true
}

function cURL_it($url)
{    
    $fields = 'validname=collectionId&cmd=Search&text=10,0,tag:"erja family"';  
    //$fields = 'validname=collectionId&cmd=Search&text=10,0,tag:"homo sapiens"';  
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
    //print $output; exit;    
    curl_close($ch);
    $ans = stripos($output,"The page cannot be found");
    $ans = strval($ans);
    if($ans != "")  return false;
    else            return $output;        
}//function cURL_it()


// /*
	
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
function clean_str($str)
{    
    $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    return $str;
}

?>