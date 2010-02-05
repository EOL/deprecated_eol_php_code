#!/usr/local/bin/php  
<?php
/*
connector for hexacorallians
*/

//exit;
//define("ENVIRONMENT", "development");
//define("ENVIRONMENT", "slave_32");
define("MYSQL_DEBUG", false);
define("DEBUG", false);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

//only on local; to be deleted before going into production
/*
$mysqli->truncate_tables("development");
Functions::load_fixtures("development");
exit;
*/

$wrap = "\n";
$wrap = "<br>";
 
$resource = new Resource(1);
print "resource id = " . $resource->id . "$wrap";
//exit;

$schema_taxa = array();
$used_taxa = array();

$id_list=array();


$total_taxid_count = 0;
$do_count = 0;


$url            ="http://hercules.kgs.ku.edu/hexacoral/anemone2/valid_species.cfm";
$home_url       ="http://hercules.kgs.ku.edu/hexacoral/anemone2/index.cfm";
$form_url       ="http://hercules.kgs.ku.edu/hexacoral/anemone2/valid_species_search.cfm";
$taxa_list = get_taxa_list($url);

$arr_desc_taxa = array();
$arr_categories = array();
$arr_outlinks = array();              

for ($i = 0; $i < count($taxa_list); $i++) 
{
    //main loop
    print $wrap;
    print $i+1 . " of " . count($taxa_list) . " id=" . $taxa_list[$i] . " ";
    $validname = $taxa_list[$i];        
    list($id,$image_url,$description,$desc_pic,$desc_taxa,$categories,$taxa,$copyright,$providers,$creation_date,$photo_credit,$outlinks) 
    = process($form_url,$validname);

    if(trim($taxa) == "")
    {   
        print " --blank taxa--";
        continue; 
        //exit(" $philid blank taxa exists");
    }
    //print"$id<hr> --- $image_url<hr> --- $description<hr> --- $desc_pic<hr> --- $desc_taxa<hr> --- $categories<hr> --- $taxa<hr> --- $copyright<hr> $providers<hr> --- $creation_date<hr> --- $photo_credit<hr> --- $outlinks<hr> --- ";
    
    $desc_taxa = str_ireplace("animals sre filtered", "animals are filtered", $desc_taxa);
    
    //$categories="xxx";
    $outlinks = utf8_encode($outlinks);
    $desc_pic = utf8_encode($desc_pic);
    $desc_taxa = utf8_encode($desc_taxa);
    
    /* desc_taxa is no longer included    
    if($desc_taxa != "")$desc_pic .= "<br><br>$desc_taxa";   
    */
    
    $desc_pic = $desc_pic . "<br>" . "Created: $creation_date";
    
    $desc_pic = str_ireplace("<i>comb scales</i>", "comb scales", $desc_pic);
    $desc_pic = str_ireplace("<i>lateral plate</i>", "lateral plate", $desc_pic);
    $desc_pic = str_ireplace("<i>spinulose hairs</i>", "spinulose hairs", $desc_pic);
    $desc_pic = str_ireplace("<i>median ventral brush</i>", "median ventral brush", $desc_pic);
    
     
    if(in_array($taxa . $desc_taxa, $arr_desc_taxa))$desc_taxa="";
    else                                            $arr_desc_taxa[] = $taxa . $desc_taxa;     

    if(in_array($taxa . $categories, $arr_categories))$categories="";
    else                                              $arr_categories[] = $taxa . $categories;     
    
    if(in_array($taxa . $outlinks, $arr_outlinks))$outlinks="";
    else                                          $arr_outlinks[] = $taxa . $outlinks;     

    //new
    $desc_taxa="";
    
    if($categories != "")$desc_taxa .= "<hr>Categories:<br>$categories";   
    if($outlinks != "")  $desc_taxa .= "<hr>Outlinks:<br>$outlinks";
    
    //print"<hr><hr>";    
    //print"<hr>";     

    $taxon = str_replace(" ", "_", $taxa);
    if(@$used_taxa[$taxon])
    {
        $taxon_parameters = $used_taxa[$taxon];
    }
    else
    {
        $taxon_parameters = array();
        $taxon_parameters["identifier"] = "CDC_" . $taxon; //$main->taxid;
        $taxon_parameters["scientificName"]= $taxa;
        $taxon_parameters["source"] = $home_url;
        $used_taxa[$taxon] = $taxon_parameters;            
    }

    if(1==1)
    {
        //if($do_count == 0)//echo "$wrap$wrap phylum = " . $taxa . "$wrap";

        $dc_source = $home_url;       

        $do_count++;        
        $agent_name = $photo_credit;
        $agent_role = "photographer";            
        
        
        /* for debugging
        $image_url = "http://127.0.0.1/test.tif";
        $image_url = "http://www.findingspecies.org/indu/images/YIH_13569_MED_EOL.TIFF";
        */
        
        // /* just debug; no images for now
        $data_object_parameters = get_data_object("image",$taxon,$do_count,$dc_source,$agent_name,$agent_role,$desc_pic,$copyright,$image_url,"");               
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                         
        // */
        

        /* no text descriptions per Katja
        if($desc_taxa != "")
        {
            $temp = trim(strip_tags($desc_taxa));                        
            if(substr($temp,0,9)  != "Outlinks:")
            {
                if(substr($temp,0,11) == "Categories:") $title="Categories";
                //$desc_taxa="<b>Discussion on disease(s) caused by this organism:</b>" . $desc_taxa;                        
                $do_count++;
                $agent_name = $providers;
                $agent_role = "source";            
                $data_object_parameters = get_data_object("text",$taxon,$do_count,$dc_source,$agent_name,$agent_role,$desc_taxa,$copyright,$image_url,$title);                           
                $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                                 
            }            
        }
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

echo "$wrap$wrap Done processing.";
exit("<hr>-done-");

function get_data_object($type,$taxon,$do_count,$dc_source,$agent_name,$agent_role,$description,$copyright,$image_url,$title)   
{        
    //$description = "<![CDATA[ $description ]]>";
    $dataObjectParameters = array();
        
    if($type == "text")
    {            
        $dataObjectParameters["title"] = $title;            

        //start subject        
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        
        $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";        
        //$subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#RiskStatement";        
        //$subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Diseases";
        
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

function get_taxa_list($file)
{
    global $wrap;
        
    $str = Functions::get_remote_file($file);        
    $beg='<SELECT name=validname size=1>'; $end1='</SELECT>'; 
    $str = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    
    $str = str_ireplace('<OPTION value="' , "&arr[]=", $str);	
    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	       

    $arr2=array();
    for ($i = 0; $i < count($arr); $i++) 
    {
        $temp = "xxx" . $arr[$i];
                        
        $beg='xxx'; $end1='"'; 
        $sciname = trim(parse_html($temp,$beg,$end1,$end1,$end1,$end1,""));            
        $arr2["$sciname"]=1;
    }    
    $arr = array_keys($arr2);    
    array_splice($arr, 0, 1);   //deletes first array element
    array_pop($arr);            //deletes last array element
    //print"<pre>";print_r($arr);print"</pre>";    
    //print"<hr>$str";
    return $arr;    
}



function process($url,$validname)
{
    global $wrap;
    
    $contents = cURL_it($validname,$url);
    if($contents) print "";
    else print "$wrap bad post [$validname] $wrap ";

    $arr = parse_contents($contents);
    return $arr;        
}

function get_references($url)
{
    global $wrap;
    
    $table = Functions::get_remote_file($url);                
    $beg='<th align=left><b>Nomenclature Notes </b></td></th>'; $end1='</table>'; 
    $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));                
    $temp = substr($temp,5,strlen($temp));//to remove the '</tr>' at the start of the string        
    $temp = str_replace(array("<tr class=listrow1 >","<tr class=listrow2 >","<tr  class=listrow2  >"), "<tr>", $temp);			
                                                       
    //print $temp; exit;
    
    $temp = str_ireplace('<tr>' , "", $temp);	
    $temp = trim(str_ireplace('</tr>' , "***", $temp));	
    $temp = substr($temp,0,strlen($temp)-3);//remove last '***'
    $arr = explode("***", $temp);
    $arr_images=array();
    
    for ($i = 0; $i < count($arr); $i++) 
    {
        $str = $arr[$i];
        $str = str_ireplace('<td>' , "", $str);	
        $str = trim(str_ireplace('</td>' , "***", $str));	
        $str = substr($str,0,strlen($str)-3);//remove last '***'
        $arr2 = explode("***", $str);    
        $arr_images[]=$arr2;
    }
    print"<pre>";print_r($arr_images);print"</pre>";
    //exit("<hr>stop muna");
}
function get_synonyms($url)
{
    global $wrap;
    
    $table = Functions::get_remote_file($url);            
    $beg='<TH><B>Authorship</b></TH>'; $end1='</table>'; 
    $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));                
    $temp = substr($temp,5,strlen($temp));//to remove the '</tr>' at the start of the string    
    $temp = str_replace(array("<tr class=listrow1 >","<tr class=listrow2 >","<tr  class=listrow2  >"), "<tr>", $temp);			
    //print "<hr>$temp"; exit;
    
    $temp = str_ireplace('<tr>' , "", $temp);	
    $temp = trim(str_ireplace('</tr>' , "***", $temp));	
    $temp = substr($temp,0,strlen($temp)-3);//remove last '***'
    $arr = explode("***", $temp);
    $arr_images=array();
    
    for ($i = 0; $i < count($arr); $i++) 
    {
        $str = $arr[$i];
        $str = str_ireplace('<td>' , "", $str);	
        $str = trim(str_ireplace('</td>' , "***", $str));	
        $str = substr($str,0,strlen($str)-3);//remove last '***'
        $arr2 = explode("***", $str);    
        $arr_images[]=$arr2;
    }
    print"<pre>";print_r($arr_images);print"</pre>";
    //exit("<hr>stop muna");
}

function get_images($url)
{
    global $wrap;
    
    $table = Functions::get_remote_file($url);            
    $beg='<th>Caption</th>'; $end1='<th colspan=5 >For more information'; 
    $temp = trim(parse_html($table,$beg,$end1,$end1,$end1,$end1,""));            
    
    $temp = substr($temp,5,strlen($temp));//to remove the '</tr>' at the start of the string    
    $temp = substr($temp,0,strlen($temp)-5);//to remove the '<tr>' at the end of the string    
    $temp = str_replace(array("<tr class=listrow1 >","<tr class=listrow2 >","<tr  class=listrow2  >"), "<tr>", $temp);			

    //print $temp; exit;
    
    $temp = str_ireplace('<tr>' , "", $temp);	
    $temp = trim(str_ireplace('</tr>' , "***", $temp));	
    $temp = substr($temp,0,strlen($temp)-3);//remove last '***'
    $arr = explode("***", $temp);
    $arr_images=array();
    
    for ($i = 0; $i < count($arr); $i++) 
    {
        $str = $arr[$i];
        $str = str_ireplace('<td>' , "", $str);	
        $str = trim(str_ireplace('</td>' , "***", $str));	
        $str = substr($str,0,strlen($str)-3);//remove last '***'
        $arr2 = explode("***", $str);    
        $arr_images[]=$arr2;
    }
    print"<pre>";print_r($arr_images);print"</pre>";
    //exit("<hr>stop muna");
}

function parse_contents($str)
{
    global $wrap;
    /* it can be:
    <a href="speciesdetail.cfm?genus=Abyssopathes&subgenus=&species=lyra&subspecies=&synseniorid=9266&validspecies=Abyssopathes%20lyra&authorship=%28Brook%2C%201889%29">Abyssopathes lyra (Brook, 1889)</a>
    or
    <a href="speciesdetail_for_nosyn.cfm?species=dentata&genus=Sandalolitha&subgenus=&subspecies=">Sandalolitha dentata Quelch, 1884</a>
    */
    $temp='';
    $beg='speciesdetail.cfm?'; $end1='</a>'; 
    $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    if($temp=='')	
    {
        $beg='speciesdetail_for_nosyn.cfm?'; $end1='</a>'; 
        $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
    }  

    $site_url="http://hercules.kgs.ku.edu/hexacoral/anemone2/";
    $temp = '<a href="' . $site_url . '' . $beg . $temp . "</a>";
    //get url_for_main_menu
        $beg='="'; $end1='">'; 
        $url_for_main_menu = trim(parse_html($temp,$beg,$end1,$end1,$end1,$end1,""));            
        print"$wrap url_for_main_menu[$url_for_main_menu]";    
    //end url_for_main_menu
    
    //get sciname
        $beg='">'; $end1='</a>'; 
        $sciname = trim(parse_html($temp,$beg,$end1,$end1,$end1,$end1,""));            
        print"$wrap sciname[$sciname]";
    //end sciname
    
    $main_menu = Functions::get_remote_file($url_for_main_menu);        
    //get url for images page
        $url_for_images_page="";
        //"images.cfm?&genus=Abyssopathes&subgenus=&species=lyra&subspecies=&seniorid=9266&validspecies=Abyssopathes%20lyra&authorship=%28Brook%2C%201889%29">Images</a> 
        $beg='images.cfm'; $end1='">'; 
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));            
        if($temp != "") 
        {
            $url_for_images_page = $site_url . $beg . $temp;
            print"$wrap [<a href='$url_for_images_page'>images</a>]";                
            $arr_images = get_images($url_for_images_page);            
        }else print"$wrap no images";   
        
                
    //end url for images page
    
    //get url for classification
        $url_for_classification="";
        //"showclassification2.cfm?synseniorid=2914&genus=Aiptasiogeton&subgenus=&species=eruptaurantia&subspecies=&origgenus=Actinothoe&origspecies=eruptaurantia&origsubspecies=&origsubgenus=&&validspecies=Aiptasiogeton%20eruptaurantia&authorship=%28Field%2C%201949%29">Classification</a> 
        $beg='showclassification2.cfm'; $end1='">'; 
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));            
        if($temp != "") 
        {
            $url_for_classification = $site_url . $beg . $temp;
            print"$wrap [<a href='$url_for_classification'>classification</a>]";    
        }else print"$wrap no classification";   
    //end url for classification

    //get url for strict_synonymy
        $url_for_strict_synonymy="";
        //"synonymy_strict.cfm?seniorid=2914&validspecies=Aiptasiogeton%20eruptaurantia&authorship=%28Field%2C%201949%29">Strict synonymy</a> 
        $beg='synonymy_strict.cfm'; $end1='">'; 
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));            
        if($temp != "") 
        {
            $url_for_strict_synonymy = $site_url . $beg . $temp;
            print"$wrap [<a href='$url_for_strict_synonymy'>strict_synonymy</a>]";    
            $arr_synonyms = get_synonyms($url_for_strict_synonymy);            
            
        }else print"$wrap no strict_synonymy";   
    //end url for strict_synonymy

    //get url for references
        $url_for_references="";
        //"all_mentions_of_names2.cfm?species...
        $beg='all_mentions_of_names.cfm'; $end1='">'; 
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));                    
        if($temp=="")
        {
            $beg='all_mentions_of_names2.cfm'; $end1='">'; 
            $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));            
        }        
        if($temp != "") 
        {
            $url_for_references = $site_url . $beg . $temp;
            print"$wrap [<a href='$url_for_references'>references</a>]";    
            $arr_references = get_references($url_for_references);            

        }else print"$wrap no references";   

    //end url for references

    //get url for common_names
        $url_for_common_names="";
        //"common.cfm?seniorid=2914&validspecies=Aiptasiogeton%20eruptaurantia&authorship=%28Field%2C%201949%29">Strict synonymy</a> 
        $beg='common.cfm'; $end1='">'; 
        $temp = trim(parse_html($main_menu,$beg,$end1,$end1,$end1,$end1,""));            
        if($temp != "") 
        {
            $url_for_common_names = $site_url . $beg . $temp;
            print"$wrap [<a href='$url_for_common_names'>common_names</a>]";    
        }else print"$wrap no common_names";   
    //end url for common_names
    


    
    
    print"<hr>$main_menu"; 
    exit("<hr>ditox");
    //========================================================================================	       
    return array ($taxa);    
}//function parse_contents($contents)

function cURL_it($validname,$url)
{    
    $fields = 'validname=' . $validname;  
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
}//function cURL_it($philid)

// /*
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
				if(	substr($str,$i,$end1_len) == $end1 or 
					substr($str,$i,$end2_len) == $end2 or 
					substr($str,$i,$end3_len) == $end3 or 
					substr($str,$i,$end4_len) == $end4 or 
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
    $str = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    return $str;
}

?>