<?php
/* connector for Ascidians
estimated execution time: 

Connector screen scrapes the partner website.

*/
$timestart = microtime(1);

//print get_href_from_anchor_tag('<a href="../../pycnoclavellidae/Pycnoclavella_taureanensis/pycnoclavellataureanensis1.htm">Pycnoclavella taureanensis</a>');
//exit;
//print chr(38);exit;

include_once(dirname(__FILE__) . "/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$not_found=0;
$wrap = "\n"; 
$wrap = "<br>"; 
 
$resource = new Resource(1);
//exit($resource->id);
$url_list=array();

$schema_taxa = array();
$used_taxa = array();

$dc_source = "";
$keys_url  = "";

$path = dirname(__FILE__) . "/files/";
//$txt_file = $path . "species_list.txt";
$txt_file = $path . "species_list_with_synonyms.txt";
//exit;

$path="http://www.ascidians.com/families/";
$urls = array( 0  => array( "url" => $path . "didemnidae/didemnidae.htm"                ,"family"=>"Didemnidae"         , "active" => 1),   //
               1  => array( "url" => $path . "polycitoridae/polycitoridae.htm"          ,"family"=>"Polycitoridae"      , "active" => 0),   //
               2  => array( "url" => $path . "polyclinidae/polyclinidae.htm"            ,"family"=>"Polyclinidae"       , "active" => 0),   //                
               3  => array( "url" => $path . "pycnoclavellidae/pycnoclavellidae.htm"    ,"family"=>"Pycnoclavellidae"   , "active" => 0),   //                
               4  => array( "url" => $path . "ascidiidae/ascidiidae.htm"                ,"family"=>"Ascidiidae"         , "active" => 0),   //                
               5  => array( "url" => $path . "agnesiidae/agnesiidae.htm"                ,"family"=>"Agnesiidae"         , "active" => 0),   //empty                
               6  => array( "url" => $path . "cionidae/cionidae.htm"                    ,"family"=>"Cionidae"           , "active" => 0),   //                
               7  => array( "url" => $path . "corellidae/corellidae.htm"                ,"family"=>"Corellidae"         , "active" => 0),   //                
               8  => array( "url" => $path . "holozoidae/holozoidae.htm"                ,"family"=>"Holozoidae"         , "active" => 0),   //                
               9  => array( "url" => $path . "octactemidae/octactemidae.htm"            ,"family"=>"Octacnemidae"       , "active" => 0),   //empty                
               10 => array( "url" => $path . "perophoridae/perophoridae.htm"            ,"family"=>"Perophoridae"       , "active" => 0),   //                
               11 => array( "url" => $path . "plurellidae/plurellidae.htm"              ,"family"=>"Plurellidae"        , "active" => 0),   //empty                
               12 => array( "url" => $path . "botryllidae/botryllidae.htm"              ,"family"=>"Botryllidae"        , "active" => 0),   //                
               13 => array( "url" => $path . "molgulidae/molgulidae.htm"                ,"family"=>"Molgulidae"         , "active" => 0),   //       
               14 => array( "url" => $path . "pyuridae/pyuridae.htm"                    ,"family"=>"Pyuridae"           , "active" => 0),   //                
               15 => array( "url" => $path . "styelidae/styelidae.htm"                  ,"family"=>"Styelidae"          , "active" => 0),   //                
               16 => array( "url" => $path . "thaliacea/thaliacea.htm"                  ,"family"=>"Thaliacea (Salps)"  , "active" => 0)
             );
$do_count=0;
$i=0;
foreach($urls as $path)
{    
    if($path["active"])
    {        
        print $i+1 . ". " . $path["url"] . "$wrap";        
        process_file1($path["url"],$i,$path["family"]); 
        /*
        process_file1 will assemble the $url_list array:
        [141] => Array
        (
            [sciname] => Lissoclinum fragile
            [family] => Didemnidae
            [url] => http://www.ascidians.com/families/didemnidae/Lissoclinum_fragile/lissoclinumfragile2.htm
        )
        [142] => Array
        (
            [sciname] => Lissoclinum patella
            [family] => Didemnidae
            [url] => http://www.ascidians.com/families/didemnidae/Lissoclinum_patella/lissoclinumpatella.htm
        )
        [143] => Array
        (
            [sciname] => Lissoclinum patella
            [family] => Didemnidae
            [url] => http://www.ascidians.com/families/didemnidae/Lissoclinum_patella/lissoclinumpatella2.htm
        )                
        */        
    }
    $i++;
}    

print"<pre>";print_r($url_list);print"</pre>";


//start - 2nd part is loop through the $url_list and extract the image dataobject
process_file2($url_list);
//end - 2nd part is loop through the $url_list and extract the image dataobject


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
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";

exit("\n\n Done processing.");
//######################################################################################################################
//######################################################################################################################
//######################################################################################################################
function process_file2($url_list)
{        

    global $wrap;
    global $used_taxa;
    
    //start first get a unique list of scinames    
    $temp=array();
    foreach($url_list as $arr)
    {
        $temp[$arr["sciname"]]=1;
    }
    $temp = array_keys($temp);
    $unique_sciname=array();    
    foreach($temp as $sn)
    {
        foreach($url_list as $sn2)
        {
            if($sn == $sn2["sciname"])
            {
                $unique_sciname[]=array("sciname"=>$sn , "url"=> $sn2["url"], "family"=>$sn2["family"]);
                break;
            }
        }    
    }        
    print"<pre>";print_r($unique_sciname);print"<pre>";
    //exit;
    //end first get a unique list of scinames    
    
    
    $i=0;
    foreach($unique_sciname as $arr)
    {        
        //if($i >= 5)break; //debug        //ditox
        
        $i++;
        // if(in_array($i,array(8))){
        if(true)
        {
            $sciname = $arr["sciname"];
            $desc = "image caption";
            
            if($sciname == "")print "jjj";            
            print "$i. $sciname $wrap";
            //print "$desc";                      
            
            $dc_source = $arr["url"];
            $family = $arr["family"];
            
            $arr_agents=array();
            $dc_rights = "Compiled by eli...";
            $arr_agents[]=array("name"=>"Dr. elijoshua", "role"=>"compiler" ,"homepage"=>"");    
    
            assign_variables($sciname,$desc,$arr_agents,$dc_rights,$dc_source,$family);
                                    
        }        
    }//main loop
    
    //exit;    
        
}//end function process_file2($file)

function proc_species_page($url,$sciname,$family)
{
    global $wrap;
    global $url_list;
    
    $url_list[] = array("sciname"=>$sciname,"family"=>$family,"url"=>$url);
    $first_part_of_url = substr($url,0,strrpos($url, "/"));
    
    //#############################################################################################################
    //#############################################################################################################
    //start - first part is get the individual URL's of each image
    $img_urls=array();
    $str = Functions::get_remote_file($url);           
    $str = strip_tags($str,"<a>");
    //print"<hr>$str";
    
    $str = str_ireplace('<a href="' , 'xxx<a href="', $str);	
    $str = str_ireplace('xxx' , "&arr[]=", $str);	
    $str=trim($str);
    $arr=array();	
    parse_str($str);	
    print "after parse_str recs = " . count($arr) . "$wrap $wrap";	
    //print"<pre>";print_r($arr);print"</pre>";
    //exit;
    
    foreach($arr as $temp_url)
    {
        $temp = get_href_from_anchor_tag(trim($temp_url));
        //print "$temp $wrap";
        if(substr($temp,0,2)!=".." and substr($temp,0,4)!="http" and substr($temp,0,4)!="file")
        {
            $url_list[] = array("sciname"=>$sciname,"family"=>$family,"url"=>$first_part_of_url . "/" . $temp);
        }
    } 
    //exit;
    
    //print"<pre>";print_r($url_list);print"</pre>";
    //exit;
    //end - first part is get the individual URL's of each image
    
    //#############################################################################################################
    //#############################################################################################################
    
    
    //#############################################################################################################
    //#############################################################################################################
   
    return;
}
function process_file1($file,$doc_id,$family)
{        
    global $wrap;
    global $used_taxa;
    global $url_list;
    
    print "$wrap";    
    $str = Functions::get_remote_file($file);        
    
 
    /*
    $str = str_ireplace('<td height="26">&nbsp;</td>','<td height="26"></td>',$str);
    $str = str_ireplace('<td height="30" colspan="5">&nbsp;</td>','<td height="30" colspan="5"></td>',$str);
    */
    


    
    //print "<hr>$str"; exit;
    
    $beg='<table'; $end1='</table>';
    $str = $beg . " " . trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"")) . " " . $end1;            
    
    $species_url_arr = get_tabular_data($str,$file);
      
    print"<pre>";print_r($species_url_arr);print"</pre>";//exit;//this is count of thumbnails in the thumbnail summary
    
    $i=0;
    foreach($species_url_arr as $str)
    {
        $i++;
        print"$i. ";
        //if($i >= 5)break; //debug        //ditox                
        //if(in_array($i,array(1,3)))        
        if(true)
        {   
            if($str["active"])
            {
                $temp = proc_species_page($str["url"],$str["caption"],$family);
                print $str["url"];
            }
            else print "inactive";
        }   
        print"<br>";     
    }//main loop    
    
    
    //print"<pre>";print_r($url_list);print"</pre>";
    //exit;            
}//end function process_file1($file)
function get_tabular_data($str,$file)
{
    /*
    <table>
        <tr>
            <td>value 1</td>
            <td>value 2</td>
            <td>value 3</td>
        </tr>
        <tr>
            <td>field 1</td>
            <td>field 2</td>
            <td>field 3</td>
        </tr>
    </table>
    */
    global $wrap;

    $str = str_ireplace("&" , "___", $str);    
        
    $str = clean_str($str);    
    
    $str = str_ireplace('<tr align="center">' , "&arr[]=", $str);//1    
    
    $str = str_ireplace('<tr valign="bottom" align="center">' , "&arr[]=", $str);//2
    $str = str_ireplace('<tr valign="top" align="center">' , "&arr[]=", $str);//2
    $str = str_ireplace('<tr align="center" valign="bottom">' , "&arr[]=", $str);//2

    $str = str_ireplace('target="_self"' , '', $str);//2
    $str = strip_tags($str,"<tr><td><a>");
        
    $str=trim($str);
    
    $arr=array();	
    parse_str($str);	
    //print "after parse_str recs = " . count($arr) . "$wrap $wrap";
    $arr_tr = $arr;
    
    print"<pre>";print_r($arr);print"</pre>";//exit;
    
    $img_url=array();
    $img_txt=array();
    $i=0;
    foreach($arr_tr as $tr)
    {
        $tr = str_ireplace("___" , "&", $tr);    
        $i++;
        
        $tr = str_ireplace("<td" , "xxx<td"     , $tr);
        $tr = str_ireplace('xxx' , "&arr[]=" , $tr);	    
        $arr=array();
        parse_str($tr);	
         /*        
        print "after parse_str recs = " . count($arr) . "$wrap $wrap";                
        print"<pre>";print_r($arr);print"</pre>";        
        exit;
         */
        foreach($arr as $td)
        {
            if ($i % 2)$img_url[]=$td;
            else       $img_txt[]=$td;            
        }   
        
    }
    // /*
    print"<pre>";print_r($img_url);print"</pre>";        
    print"<pre>";print_r($img_txt);print"</pre>";        
    //exit;
    //*/    
    $arr=array();
    for ($i = 0; $i < count($img_url); $i++) 
    {
        $sciname = trim(strip_tags($img_txt[$i],"<a>"));        
        if(stripos($sciname, "href"))  $active=false;
        else
        {
            if($sciname == "")   $active=false;
            else                              $active=true;
        }
        
        print strlen(trim($sciname)) . " -- [$sciname] $active $wrap";
        
        $url = strip_tags($img_url[$i],"<a>");
        $url = get_href_from_anchor_tag($url);        
        if($active)
        {
            $first_part_of_url = substr($file,0,strrpos($file, "/"));
            $url = $first_part_of_url . "/" . $url;
        }            
        $arr[]=array("url" => $url, "caption" => trim($sciname), "active" => $active);        
    }
    
    //print"<pre>";print_r($arr);print"</pre>";
    //exit;
    
    return $arr;    
}



function assign_variables($sciname,$desc,$arr_agents,$dc_rights,$dc_source,$family)
{
    global $species_list;
    global $used_taxa;
    global $keys_url;
    global $wrap;
    global $not_found;    
    
    global $url_list;
        
        $taxon_identifier   = str_ireplace(" ", "_", $sciname) . "_ascidians";
        $source_url         = $dc_source;            
        $do_identifier      = str_ireplace(" ", "_", $sciname) . "_ascidians_object";            


        if(@$used_taxa[$taxon_identifier])
        {
            $taxon_parameters = $used_taxa[$taxon_identifier];
        }
        else
        {
            $taxon_parameters = array();
            $taxon_parameters["identifier"] = $taxon_identifier;
            
            /*
            $taxon_parameters["kingdom"] = trim(@$species_list["$sciname"]["Kingdom"]);
            $taxon_parameters["class"] = trim(@$species_list["$sciname"]["Class"]);
            $taxon_parameters["order"] = trim(@$species_list["$sciname"]["Order"]);
            $taxon_parameters["genus"] = trim(@$species_list["$sciname"]["Genus"]);
            */
            $taxon_parameters["family"] = $family;
            
                        
            $taxon_parameters["scientificName"]= $sciname;                    
            $taxon_parameters["source"] = $source_url;

            /*
            $taxon_parameters["commonNames"] = array();
            $arr_comname=conv_2array($comname);
            foreach ($arr_comname as $commonname) 
            {
                $commonname = str_ireplace(';' , '', $commonname);
                $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => "en"));
            }
            */
            
            /////////////////////////////////////////////////////////////
            /*
            $taxon_params["synonyms"] = array();
            $arr_synonym=conv_2array($synonymy);
            foreach ($arr_synonym as $synonym) 
            {
                $taxon_parameters["synonyms"][] = new SchemaSynonym(array("synonym" => $synonym, "relationship" => "synonym"));
            }
            */
            /////////////////////////////////////////////////////////////
            
            $taxon_parameters["dataObjects"]= array();        
            $used_taxa[$taxon_identifier] = $taxon_parameters;
        }        
        
        //start text dataobject                
        /*
        $dc_identifier  = $do_identifier;
        $desc           = $desc;
        $title          = "Physical Description";
        $subject        = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description";
        $type           = "text";
        $reference      = "";        
        $data_object_parameters = get_data_object($dc_identifier, $desc, $dc_rights, $title, $source_url, $subject, $type, $reference, $arr_agents);
        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);     
        */
        //end text dataobject                    
        
        //start text dataobject                
        //end text dataobject                            

        //start img dataobject                
        
        foreach($url_list as $rec)
        {
            if($rec["sciname"] == $sciname)
            {
                $arr = parse_image_detail_page($rec["url"]);
                
                exit;
                $dc_identifier  = $rec["url"];
                $source_url     = $rec["url"];
                $mediaurl       = $rec["url"];
                $desc           = "";
                $type           = "image";
                $title          = "";
                $subject        = "";
                $reference      = "";        
                $data_object_parameters =  
                get_data_object($dc_identifier, $desc,        $dc_rights, $title, $source_url, $subject, $type, $reference, $arr_agents, $mediaurl);                
                $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                 
            }            
        }
        

        //end img dataobject                            
        
        $used_taxa[$taxon_identifier] = $taxon_parameters;                                
        
    return "";        
}

function parse_image_detail_page($url)
{
    $str = Functions::get_remote_file($url);               
    
    $beg='Locality:'; $end1='</td>';
    $caption = $beg . " " . strip_tags(trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false)));    
    print"<hr>caption=$caption";
    

    $beg='<img src="'; $end1='"';
    $media_url = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));    
    $media_url = substr($url,0,strrpos($url, "/")) . "/" . $media_url;
    
    print"<hr>media_url=$media_url";
    
    return array($caption,$media_url);
}

function conv_2array($list)
{    
    $list = str_ireplace('and ', ',', $list);	    
    $arr = explode(",",$list);        
    for ($i = 0; $i < count($arr); $i++) 
    {
        $arr[$i]=trim($arr[$i]);
    }
    //print_r($arr);
    return $arr;
}

function get_data_object($id, $description, $dc_rights, $title, $url, $subject, $type, $reference, $arr_agents, $mediaurl=NULL)
{     
    $dataObjectParameters = array();
    
    if($type == "text")
    {   
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["title"] = $title;
        ///////////////////////////////////    
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        ///////////////////////////////////        
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/Text";    
        $dataObjectParameters["mimeType"] = "text/html";        
    }
    else
    {
        $dataObjectParameters["identifier"] = $id;    
        $dataObjectParameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        
        $dataObjectParameters["mimeType"] = "image/jpeg";
        $dataObjectParameters["mediaURL"] = $mediaurl;
    }

            /////////////////////////////////////////////////////////////
            
            foreach ($arr_agents as $g)
            {        
                $agentParameters = array();            
                $agentParameters["role"]     = $g["role"];
                $agentParameters["fullName"] = $g["name"];
                $agentParameters["homepage"] = $g["homepage"];
                $agents[] = new SchemaAgent($agentParameters);
            }            
            $dataObjectParameters["agents"] = $agents;    
            /////////////////////////////////////////////////////////////

    ///////////////////////////////////
    ///////////////////////////////////
    
    $dataObjectParameters["description"] = $description;        
    //$dataObjectParameters["created"] = $created;
    //$dataObjectParameters["modified"] = $modified;    
    
    $dataObjectParameters["language"] = "en";        
    $dataObjectParameters["source"] = $url;    

    //$dataObjectParameters["rights"] = "Copyright 2009 IUCN Tortoise and Freshwater Turtle Specialist Group";
	$dataObjectParameters["rights"] = $dc_rights;
	
    $dataObjectParameters["rightsHolder"] = "";
    $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
    
    ///////////////////////////////////
    if($reference != "")
    {
        $dataObjectParameters["references"] = array();
        $referenceParameters = array();
        $referenceParameters["fullReference"] = trim($reference);
        $references[] = new SchemaReference($referenceParameters);
        $dataObjectParameters["references"] = $references;
    }    
    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();        
    $audienceParameters = array();      
    $audienceParameters["label"] = "Expert users";      $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    $audienceParameters["label"] = "General public";    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);    
    ///////////////////////////////////
    return $dataObjectParameters;
}

function clean_str($str)
{    
    $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB"), '', $str);			
    return $str;
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

function remove_tag_with_this_needle($str,$needle)
{
    $pos = stripos($str,$needle); //get pos of needle   
    if($pos != "")
    {        
        $char="";
        $accumulate=""; $start_get=false;
        while ($char != "<") //get pos of < start tag
        {
            $pos--;
            $char = substr($str,$pos,1);
        
            if($char == " ")$start_get = true;
            if($start_get)$accumulate .= $char;                
        }
        //print "pos_of_start_tag [$pos]<br>";
        $pos_of_start_tag = $pos;
    
        //now determine what type of tag it is
        $accumulate = substr($accumulate,0,strlen($accumulate)-1);
        $accumulate = reverse_str($accumulate);
        //print "<hr>$str<hr>$accumulate";               
    
        //now find the pos of the end tag e.g. </div
        $char="";
        $pos = $pos_of_start_tag;
        $end_tag = "</" . $accumulate . ">";
        //print "<br>end tag is " . $end_tag;
        while ($char != $end_tag )
        {   
            $pos++;  
            $char = substr($str,$pos,strlen($end_tag));                
        }    
        //print"<hr>pos of end tag [$pos]<hr>";       
        $pos_of_end_tag = $pos;
        $str = remove_substr_from_this_position($str,$pos_of_start_tag,$pos_of_end_tag,strlen($end_tag));    
        if(stripos($str,$needle) != "")$str = remove_tag_with_this_needle($str,$needle);    
    
    }    
    return trim(clean_str($str));
}
function remove_substr_from_this_position($str,$startpos,$endpos,$len_of_end_tag)
{
    $str1 = substr($str,0,$startpos);
    $str2 = substr($str,$endpos+$len_of_end_tag,strlen($str));
    return $str1 . $str2;
}
function reverse_str($str)
{
    $accumulate="";
    $length = strlen($str)-1;
    for ($i = $length; $i >= 0; $i--) 
    {
        $accumulate .= substr($str,$i,1);
    }    
    return trim($accumulate);
}




function get_href_from_anchor_tag($str)
{
    $beg='href="'; $end1='"';
    $temp = trim(parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));
    return $temp;
}
?>