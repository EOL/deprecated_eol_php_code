<?php

define("FORM_URL", "http://photosynth.net/PhotosynthHandler.ashx");
define("VIEW_URL", "http://photosynth.net/view.aspx?cid=");
define("USER_URL", "http://photosynth.net/userprofilepage.aspx?user=");

class PhotosynthAPI
{    
    public static function harvest_photosynth()
    {
        global $wrap;            
        
        $schema_taxa = array();
        $used_taxa = array();            
        $records = self::get_records();             //print"<pre>";print_r($records);print"</pre>"; exit;
        $taxa_list = self::get_taxa_list($records); //print"<pre>";print_r($taxa_list);print"</pre>";//exit;        
        print("$wrap count taxa_list = " . count($taxa_list) );        
        $i=0;
        foreach($taxa_list as $taxa)
        {    
            $i++;
            print "$wrap $wrap";
            print $i . " of " . count($taxa_list) . " id=" . $taxa["name"] . " ";        
            $taxon              = $taxa["name"];
            $taxon_id           = $taxa["id"];
            $dc_source          = $taxa["source_url"];
            $classification     = $taxa["classification"];
            $arr_common_names   = $taxa["comnames"];                
            if(trim($taxon) == "")
            {   
                print " --blank taxa--";
                continue; 
            }                   
            if(@$used_taxa[$taxon]){$taxon_parameters = $used_taxa[$taxon];}
            else
            {
                $taxon_parameters = array();
                //$taxon_parameters["identifier"] = $taxon_id;                
                $taxon_parameters["kingdom"]    = ucfirst(@$classification["kingdom"]);
                $taxon_parameters["phylum"]     = ucfirst(@$classification["phylum"]);
                $taxon_parameters["class"]      = ucfirst(@$classification["class"]);
                $taxon_parameters["order"]      = ucfirst(@$classification["order"]);
                $taxon_parameters["family"]     = ucfirst(@$classification["family"]);
                $taxon_parameters["genus"]      = ucfirst(@$classification["genus"]);        
                $taxon_parameters["scientificName"]= ucfirst($taxon);
                $taxon_parameters["source"] = $dc_source;                
                $taxon_parameters["commonNames"] = array();
                foreach($arr_common_names as $commonname)
                {            
                    if($commonname)
                    {
                        $commonname = "<![CDATA[" . trim($commonname) . "]]>";
                        $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => $commonname, "language" => ""));
                    }
                }                                                
                $used_taxa[$taxon] = $taxon_parameters;            
            }        

            //start images
            foreach ($records as $rec)
            {
                if($taxon == $rec["taxon"])
                {                
                    $do_id      = $rec["do_id"];  
                    $agent      = $rec["agent"];
                    $title      = $rec["title"];
                    $dc_source  = $rec["source_url"];
                    $rightsHolder   = $rec["rightsHolder"];
                    $description    = $rec["caption"];
                    $license        = $rec["license"];                    
                    $data_object_parameters = self::get_data_object("photosynth",$taxon,$do_id,$agent,$title,$dc_source,$rightsHolder,$description,$license);
                    $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);                                     
                }            
            }
            //end images
            $used_taxa[$taxon] = $taxon_parameters;        
        }        
        foreach($used_taxa as $taxon_parameters){$schema_taxa[] = new SchemaTaxon($taxon_parameters);}        
        return $schema_taxa;    
    }
    
    public static function get_taxa_list($records)
    {
        $arr=array();
        foreach($records as $rec)
        {
            $arr[$rec["taxon"]]=array("id"=>$rec["taxon_id"],"name"=>$rec["taxon"],"classification"=>$rec["classification"],
            "source_url"=>$rec["source_url"],
            "comnames"=>$rec["comnames"]
            );
        }
        return $arr;
    }
    
    public static function get_data_object($type,$taxon,$do_id,$agent,$title,$dc_source,$rightsHolder,$description,$license)
    {            
        $dataObjectParameters = array();            
        if($type == "photosynth")
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
        //$dataObjectParameters["language"] = "en";        
        if($license != "")$dataObjectParameters["license"] = $license;        
        //else              $dataObjectParameters["license"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";              
        //==========================================================================================
        if($agent)
        {
            $agents = array();
            foreach($agent as $a)
            {  
                $agentParameters = array();
                $agentParameters["role"]     = $a["role"];
                $agentParameters["homepage"] = $a["homepage"];
                $agentParameters["logoURL"]  = "";        
                $agentParameters["fullName"] = $a["name"];
                $agents[] = new SchemaAgent($agentParameters);
            }
            $dataObjectParameters["agents"] = $agents;    
        }
        //==========================================================================================
        $audience_list = array("Expert users", "General public");
        $audiences = array();
        foreach($audience_list as $audience)
        {  
            $audienceParameters = array();
            $audienceParameters["label"]    = $audience;
            $audiences[] = new SchemaAudience($audienceParameters);
        }
        $dataObjectParameters["audiences"] = $audiences;    
        //==========================================================================================
        return $dataObjectParameters;
    }
    
    public static function get_records()
    {
        global $wrap;
        global $tag;
    
        $fields = 'validname=collectionId&cmd=Search&text=100,0,tag:"' . $tag . '"';  
        $contents = self::cURL_it(FORM_URL,$fields);    
        
        if($contents) print "";
        else print "$wrap bad post $wrap ";
        $arr = self::parse_contents($contents);
        return $arr;        
    }
    
    public static function parse_contents($str)
    {
        global $wrap;
        
        $beg='"Collections":['; $end1=']}'; 
        $str = self::parse_html($str,$beg,$end1,$end1,$end1,$end1,'');                
    
        $str = str_ireplace('{"Images":[],' , "&arr[]=", $str);	
        $arr=array(); parse_str($str);	    
    
        print"<hr>" . count($arr);
        print"<pre>";print_r($arr);print"</pre>";
        //exit;
            
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
        
        print"<pre>";print_r($final_arr);print"</pre>";//exit;
    
        $r=array();
        
        $excluded_ids = array("","");
        
        foreach($final_arr as $arr)
        {            
            if(in_array($arr["Id"], $excluded_ids))continue;            
            //print"<iframe frameborder='0' src='http://photosynth.net/embed.aspx?cid=" . $arr["Id"] . "&delayLoad=true&slideShowPlaying=true' width='500' height='300'></iframe>";    
            $arr_tags = self::get_tags_from_site($arr["Id"]);
            
            //print"<pre>333";print_r($arr_tags);print"</pre>";
            
            //=====================================================================================        
            $source_url = VIEW_URL . $arr["Id"];            
            $agent_homepage = USER_URL . $arr["OwnerFriendlyName"];                        
            $classification = self::get_classification($arr_tags);
            $sciname = $classification["scientificname"];        
            $comnames = self::get_comnames($arr_tags);
            $license = self::get_license($arr_tags);
            $caption = $arr["Description"];
            $agent=array();
            $agent[]=array("role" => "creator" , "homepage" => $agent_homepage , "name" => $arr["OwnerFriendlyName"]);
            //=====================================================================================        
            $r[]=array  (   "taxon"          => $sciname,   
                            "taxon_id"       => $arr["OwnerUserGuid"] . "_" . str_ireplace(" ","_",$sciname),   
                            "classification" => $classification,
                            "comnames"       => $comnames,
                            "do_id"          => $arr["Id"],
                            "source_url"     => $source_url,
                            "agent"          => $agent,                        
                            "thumbnailURL"   => $arr["ThumbnailUrl"],
                            "caption"        => trim($arr["Name"] . ". " . $caption . " (Image count: " . $arr["ImageCount"] . ")"),
                            "title"          => $arr["Name"],
                            "rightsHolder"   => $arr["OwnerFriendlyName"],
                            "license"        => $license
                        );        
        }        
        //exit;
        //print"<pre>";print_r($r);print"</pre>";exit;
        return $r;    
    }
    
    public static function get_tags_from_site($id)
    {
        $url = VIEW_URL . $id;                       
        print"<hr><a href='$url'>$url</a><hr>";        
                
        /* didn't work
        $str = Functions::get_remote_file("http://photosynth.net/view.aspx?cid=1235bc02-7ad4-47be-b526-e60289ee394f");
        */

        /* didn't work
        $fields = 'cid=1235bc02-7ad4-47be-b526-e60289ee394f';  
        $str = self::cURL_it("http://photosynth.net/view.aspx",$fields);                    
        */               

        //3rd party class worked                
        require_once(DOC_ROOT . 'vendor/Snoopy/Snoopy.class.php');
    	$snoopy = new Snoopy;
    	$submit_url = $url;	
    	$submit_vars["cid"] = $id;		
    	$snoopy->submit($submit_url,$submit_vars);
    	$str = $snoopy->results;        
               
        //print"<hr>$str<hr>";exit;
            
        $beg='<div id="tagCloud">'; $end1='</div>'; 
        $str = trim(self::parse_html($str,$beg,$end1,$end1,$end1,$end1,""));            
        $str = strip_tags($str);        
        /*
        taxonomy&#58;binomial&#61;
        taxonomy:binomial=
        */
        $str = str_ireplace('&#34;','"',$str);
        $str = str_ireplace('&#58;',':',$str);
        $str = str_ireplace('&#61;','=',$str);                
        $arr_tags = explode("\n",$str); 
        
        //print"<pre>";print_r($arr_tags);print"</pre>";
        
        $final=array();
        foreach($arr_tags as $r)
        {
            if(trim($r)!="")$final[]=$r;
        }
        //print"<pre>111";print_r($final);print"</pre>";//exit;
        return $final;    
    }
    
    public static function get_license($arr_tags)
    {
        /*
        dc:license=cc-by
        dc:license=cc-by-sa
        dc:license=cc-by-nc
        dc:license=cc-by-nc-sa
        dc:license=public domain    
        */        
        foreach($arr_tags as $tag)
        {
            $tag .= "xxx";
            $beg='dc:license='; $end1='"';$end2='xxx'; $license = trim(self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,''));
            if($license)break;
        }
        if($license == "cc-by")         $license = "http://creativecommons.org/licenses/by/3.0/";
        if($license == "cc-by-sa")      $license = "http://creativecommons.org/licenses/by-sa/3.0/";
        if($license == "cc-by-nc")      $license = "http://creativecommons.org/licenses/by-nc/3.0/";
        if($license == "cc-by-nc-sa")   $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        if($license == "public domain") $license = "http://creativecommons.org/licenses/publicdomain/";    
        return $license;        
    }
    
    public static function get_comnames($arr_tags)
    {
        $comnames=array();    
        foreach($arr_tags as $tag)
        {
            $beg='taxonomy:common='; $end1='"'; $temp = self::parse_html($tag,$beg,$end1,$end1,$end1,$end1,'');
            if($temp)$comnames[]=$temp;
        }
        return $comnames;
    }
    
    public static function get_classification($arr_tags)
    {   
        $kingdom="";
        $phylum="";
        $class="";
        $order="";
        $family="";
        $genus="";
        $binomial="";
        $trinomial="";
        
        
        //print"<pre>222 ";print_r($arr_tags);print"</pre>";exit;

        foreach($arr_tags as $tag)
        {
            print" -- $tag <br>";
            $tag = trim($tag) . "xxx";
            
            if($kingdom=="")  {$beg='taxonomy:kingdom=';  $end1='"';$end2='xxx'; $kingdom   = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($phylum=="")   {$beg='taxonomy:phylum=';   $end1='"';$end2='xxx'; $phylum    = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($class=="")    {$beg='taxonomy:class=';    $end1='"';$end2='xxx'; $class     = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($order=="")    {$beg='taxonomy:order=';    $end1='"';$end2='xxx'; $order     = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($family=="")   {$beg='taxonomy:family=';   $end1='"';$end2='xxx'; $family    = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($genus=="")    {$beg='taxonomy:genus=';    $end1='"';$end2='xxx'; $genus     = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($binomial=="") {$beg='taxonomy:binomial='; $end1='"';$end2='xxx'; $binomial  = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}
            if($trinomial==""){$beg='taxonomy:trinomial=';$end1='"';$end2='xxx'; $trinomial = self::parse_html($tag,$beg,$end1,$end2,$end1,$end1,'');}            
        }
        
        $scientificname="";
        if($binomial!="")$scientificname = $binomial;
        if($trinomial!="")$scientificname = $trinomial;        
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
        print"<pre>";print_r($arr);print"</pre>";//exit;
        return $arr;    
    }
    
    public static function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false)	//str = the html block
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
                
                if($exit_on_first_match)break;                
    		}		
    	}//end outer loop
        if($all == "")	
        {
            $id='';
    	    for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}		
            return $id;
        }
        elseif($all == "all") return $arr;	
    } 
    
    public static function cURL_it($url,$fields)
    {    
        $ch = curl_init();  
        curl_setopt($ch,CURLOPT_URL,$url);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // not to display the post submission        
        /*
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
        */        
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
    }    
}
?>