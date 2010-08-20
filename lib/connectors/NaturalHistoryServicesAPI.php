<?php
define("SPECIES_URL", "http://www.rkwalton.com/");
define("IMAGE_URL", "");

class NaturalHistoryServicesAPI
{
    public static function get_all_taxa()
    {
        //$arr_acknowledgement = self::prepare_acknowledgement();
        //print"<pre>page_taxa: ";print_r($arr_acknowledgement);print"</pre>";exit;
    
        $all_taxa = array();
        $used_collection_ids = array();
        
        $domain="http://www.rkwalton.com/";        
        
        $urls = array( 0  => array( "path1" => $domain . "jump.html" , 
                                    "path2" => $domain . "jump/jsdata.html" , 
                                    "path3" => $domain . "jump/datatable.htm" , 
                                    "ancestry" => array(
                                                        "kingdom" => "Animalia",
                                                        "phylum" => "Arthropoda",
                                                        "class" => "Arachnida",
                                                        "order" => "Araneae",
                                                        "family" => "Salticidae",
                                                        "taxon_source_url" => $domain . "jump.html"
                                                       ),
                                    "active" => 1
                                  ),  // Salticidae - Jumping Spiders
                       1  => array( "path1" => $domain . "wasps.html" , 
                                    "path2" => $domain . "" , 
                                    "path3" => $domain . "" , 
                                    "ancestry" => array(
                                                        "kingdom" => "Animalia",
                                                        "phylum" => "Arthropoda",
                                                        "class" => "Insecta",
                                                        "order" => "Hymenoptera",
                                                        "family" => "",
                                                        "taxon_source_url" => $domain . "wasps.html"
                                                       ),
                                    "active" => 1
                                  ),  // Other Hymenopterans

                       2  => array( "path1" => $domain . "waspstwo.html" , 
                                    "path2" => $domain . "" , 
                                    "path3" => $domain . "" , 
                                    "ancestry" => array(
                                                        "kingdom" => "Animalia",
                                                        "phylum" => "Arthropoda",
                                                        "class" => "Insecta",
                                                        "order" => "Hymenoptera",
                                                        "family" => "",
                                                        "taxon_source_url" => $domain . "waspstwo.html"
                                                       ),
                                    "active" => 1
                                  ),  // Other Hymenopterans




                     );



        foreach($urls as $url)
        {
            if($url["active"])
            {
                $page_taxa = self::get_NHS_taxa($url["path1"],$url["path2"],$url["path3"],$url["ancestry"]); 
                
                /*debug
                print"<hr>website: " . $url["path1"] . "<br>";
                print"page_taxa count: " . $url["path1"] . " -- " . count($page_taxa) . "<hr>";
                */                
                //print"<pre>page_taxa: ";print_r($page_taxa);print"</pre>";                        
                /*
                if($page_taxa)
                {
                    foreach($page_taxa as $t) $all_taxa[] = $t;
                }
                */
                $all_taxa = array_merge($all_taxa,$page_taxa);                                    
            }
        }
        /* debug
        print"<hr><pre>all_taxa: ";print_r($all_taxa);print"</pre>";        
        print"total: " . count($all_taxa);        
        */
        return $all_taxa;
    }
    
    public static function get_NHS_taxa($url1,$url2,$url3,$ancestry)
    {
        global $used_collection_ids;
        
        $response = self::search_collections($url1,$url2,$url3,$ancestry);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            
            $used_collection_ids[$rec["sciname"]] = true;
        }        
        return $page_taxa;
    }    
    
    public static function search_collections($url1,$url2,$url3,$ancestry)//this will output the raw (but structured) output from the external service
    {
        $html = Functions::get_remote_file_fake_browser($url1);
        $html1 = self::clean_html($html);
        
        $html = Functions::get_remote_file_fake_browser($url2);
        $html2 = self::clean_html($html);

        $html = Functions::get_remote_file_fake_browser($url3);
        $html3 = self::clean_html($html);

        //print $html;exit;
        
        $arr_url_list = self::get_url_list($html1);        
        
        
        $arr_location_detail = self::get_location_detail($html2);        
        $arr_video_detail = self::get_video_detail($html3,$arr_location_detail);        
        
        $arr_video_info = self::get_video_info($html2,$arr_video_detail);        
        
        
        //print"<pre>";print_r($arr_video_info);print"</pre>";
        //print"<pre>";print_r($arr_video_detail);print"</pre>";
        //print"<pre>";print_r($arr_location_detail);print"</pre>";
        //exit;                
        
        $response = self::scrape_species_page($arr_url_list,$arr_video_info,$ancestry);
        
        return $response;//structured array
    }        

    public static function get_location_detail($str)
    {
        $arr_location_detail=array();                
        $pos = stripos($str,"Collection Points\n</h3>");
        if(is_numeric($pos)) 
        {
            //print "has location";
        }
        else return $arr_location_detail;
        
        $str = substr($str,$pos,strlen($str));
        //print "<hr>$str";
        
        //start special case        
        $str = str_ireplace('<br />\n<br />' , '<br />', $str);	
        $str = str_ireplace('© Richard K. Walton' , '', $str);	
        //end special case
        
        
        $str = str_ireplace('<br />' , 'xxx<br />', $str);	
        $str = str_ireplace('xxx' , "&arr[]=", $str);	
        $arr = array(); parse_str($str);	            
        //print"<pre>";print_r($arr);print"</pre>";exit;                

        foreach($arr as $r)
        {
            $arr_temp = explode("-", $r);            
            //
            $id = strip_tags(trim(@$arr_temp[0]));        
            $id = str_ireplace(array(" ", "\n", "\r", "\t", "\o", "\xOB","\xA0", "\xAO","\xB0", '\xa0', chr(13), chr(10), '\xaO'), '', $id);			
            $detail = self::clean_str(strip_tags(trim(@$arr_temp[1])));
            $arr_location_detail["$id"]=$detail;
        }    
        //print"<pre>";print_r($arr_location_detail);print"</pre>";exit;                        
        return $arr_location_detail;
    }
    
    
    public static function get_video_detail($str,$arr_location_detail)
    {
        $arr_video_detail=array();                
        
        //start special case        
        //$str = str_ireplace('117a&amp;b' , '117 a&amp;b', $str);	                
        $str = str_ireplace('&amp;' , '&', $str);	

        $str = str_ireplace('36 a&b' , '36a&b', $str);	        
        $str = str_ireplace('39 a&b' , '39a&b', $str);	        
        $str = str_ireplace('62 a&b' , '62a&b', $str);	        
        $str = str_ireplace('63 a&b' , '63a&b', $str);	        
        $str = str_ireplace('64 a&b' , '64a&b', $str);	        
        $str = str_ireplace('69 a&b' , '69a&b', $str);	        

        //end special case        
        
        $str = str_ireplace('&' , '__', $str);	                
        $str = str_ireplace('<tr' , 'xxx<tr', $str);	
        $str = str_ireplace('xxx' , "&arr[]=", $str);	
        $arr = array(); parse_str($str);	            
        //print"<pre>";print_r($arr);print"</pre>";exit;                
        foreach($arr as $r)
        {
            $temp = str_ireplace('</td>' , '|||', $r);	
            $temp = strip_tags($temp);
            $temp = str_ireplace('__' , '&', $temp);//1
            
            //print "$temp<br>";            
            $arr_temp = explode("|||", $temp);            
            //print"<pre>";print_r($arr_temp);print"</pre>";
            
            $id = trim($arr_temp[0]);
            $location_code = trim($arr_temp[4]);
            $arr_video_detail["$id"]=array("date_acquired"=>trim($arr_temp[2]),
                                           "location"=>trim($arr_temp[3]), 
                                           "location_code"=>$location_code,
                                           "location_detail"=>@$arr_location_detail["$location_code"]
                                          );            
        }
        //print"<pre>";print_r($arr_video_detail);print"</pre>";        
        return $arr_video_detail;
    }
    
    public static function get_video_info($str,$arr_video_detail)
    {
        $arr_video_info=array();        
        //start special case        
        $str = str_ireplace('(R:107.108)' , '(R:107,108)', $str);	                
        $str = str_ireplace('(:' , '(', $str);	                
        /*
        $str = str_ireplace('36a&b' , '36 a&b', $str);	        
        $str = str_ireplace('39a&b' , '39 a&b', $str);	        
        $str = str_ireplace('62a&b' , '62 a&b', $str);	        
        $str = str_ireplace('63a&b' , '63 a&b', $str);	        
        $str = str_ireplace('64a&b' , '64 a&b', $str);	        
        $str = str_ireplace('69a&b' , '69 a&b', $str);	        
        */
        //$str = str_ireplace('117a&b' , '117 a&b', $str);	         	
        $str = str_ireplace('1a&b' , '1a,1b', $str);	        
        $str = str_ireplace('9a&b' , '9a,9b', $str);	        
        $str = str_ireplace('20a&b' , '20a,20b', $str);	        
        //end special case
        
        $str = str_ireplace('&' , '__', $str);	        
        $str = str_ireplace('<li>' , 'xxx<li>', $str);	
        $str = str_ireplace('xxx' , "&arr[]=", $str);	
        $arr = array(); parse_str($str);	            
        //print"<pre>";print_r($arr);print"</pre>";//exit;                
        $i=0;
        foreach($arr as $r)
        {
            $sciname=""; $rec_nos=""; $i++;
            if(preg_match("/<em>(.*?)<\/em>/", $r, $matches))$sciname = $matches[1];                        
            if(preg_match("/\((.*?)\)/", $r, $matches))$rec_nos = $matches[1];                        
            
            $rec_nos = str_ireplace('R:' , '', $rec_nos);	
            $rec_nos = str_ireplace('__' , '&', $rec_nos);	//2
            
            $arr_rec_nos = explode(",", $rec_nos);
               
            //print "$i. [$sciname] [$rec_nos]<br>";
            //print"<pre>";print_r($arr_rec_nos);print"</pre>";
            
            //start <br> delimited locations
            $location="";
            foreach($arr_rec_nos as $rec_no)
            {
                $location .= "date acquired: " . @$arr_video_detail["$rec_no"]["date_acquired"] . " | "
                             . @$arr_video_detail["$rec_no"]["location"] . ", "
                             . @$arr_video_detail["$rec_no"]["location_detail"] 
                             . "<br> <br>";
            }   
            //end <br> delimited locations            
            
            $arr_video_info[]=array("sciname"=>$sciname,
                                    "rec_nos"=>$arr_rec_nos,
                                    "locations"=>$location,
                                    "used"=>""
                                   );
            
        }
        //print"<pre>";print_r($arr_video_info);print"</pre>";
        return $arr_video_info;
    }
    
    
    
    public static function get_url_list($str)
    {
        $arr_url_list=array();
        $str = str_ireplace('<a href="' , 'xxx<a href="', $str);	
        $str = str_ireplace('xxx' , "&arr[]=", $str);	
        $arr = array(); parse_str($str);	            
        //print"<pre>";print_r($arr);print"</pre>";exit;        
        $loop=0;
        $i=0;
        foreach($arr as $r)
        {
            //$loop++;if($loop >= 10)break; //debug to limit the no. of records                                
            $video_url=""; $thumb_url="";                
            if(preg_match("/href=\"(.*?)\"/", $r, $matches))$video_url = $matches[1];                
            if(preg_match("/src=\"(.*?)\"/", $r, $matches))$thumb_url = $matches[1];
            if($video_url != "" and $thumb_url != "")
            {
                $i++;    
                $arr_url_list[]=array("video_url"=>SPECIES_URL . $video_url,"thumbnail_url"=>SPECIES_URL . $thumb_url);                    
            }            
        }                
        //print"<pre>";print_r($arr_url_list);print"</pre>"; exit;//debug          
        
        return $arr_url_list;
    }

    public static function scrape_species_page($arr_url_list,$arr_video_info,$ancestry)
    {
        //print"<pre>";print_r($ancestry);print"</pre>"; exit;

        $arr_acknowledgement = self::prepare_acknowledgement();
        //print"<pre>";print_r($arr_acknowledgement);print"</pre>"; //exit;                
        
        $arr_scraped=array();
        $arr_photos=array();
        $arr_sciname=array();
        
        $ctr=0;
        foreach($arr_url_list as $rec)
        {
            $sourceURL=$rec["video_url"];
            $html = Functions::get_remote_file_fake_browser($sourceURL);            
            $html = self::clean_html($html);            
            //print $html;exit;
            //=============================================================================================================
             
            /*
            Per PL:
            - use \s* for spaces in your pattern.
            - put ? in here: (.*?) if you want your pattern to be not greedy, without ? will be greedy.
            */ 
            
            $species="";
            /* get string between <title> and </title> */
            //if(preg_match("/<!--\s*title\s*name=\"Species\"\s*-->(.*?)<!--\s*title/ims", $html, $matches))
            if(preg_match("/<title>(.*?)<\/title/ims", $html, $matches))
            {   
                if(is_numeric(stripos($matches[1],"xx")))continue;
                            
                $title = trim(strip_tags($matches[1]));
                
                //special case
                if($title == "Leaf-cutting Ants - Atta")$title="Atta sp. - Leaf-cutting Ants";                
                if($title == "Alabgrus texanus - Braconid Wasp")$title="Alabagrus texanus - Braconid Wasp";                
                //end special case
                
                $piece = self::get_strings_between_char($title,"-");
                $sciname = trim(@$piece[0]);
                $desc = trim(@$piece[1]);                

                
                if(in_array($sciname, array("Solitary Wasps","Solitary Wasp Video","Critters","404 Not Found")))continue;
                
                $ctr++;                        
                //print"<br>$ctr. $sciname";
            }            

            //=============================================================================================================
            
            $acknowledgement = self::get_acknowledgement($sciname, $arr_acknowledgement);
            
            
            
            
            
            //=============================================================================================================
            //=============================================================================================================
            //=============================================================================================================
            
            //text object agents
            $agent=array();
            $agent[]=array("role" => "author" , "homepage" => "http://www.rkwalton.com" , "name" => "Richard K. Walton");
            
            
            //"mimeType"=>"video/mp4",
            
            $arr_temp=self::get_location($arr_video_info,$sciname);
            $location       = $arr_temp[0];
            $arr_video_info = $arr_temp[1];            
            
            $arr_photos["$sciname"][] = array("identifier"=>$rec["video_url"],
                                  "mediaURL"=>str_ireplace(".html",".mp4",$rec["video_url"]),
                                  "mimeType"=>"video/mp4",
                                  "dataType"=>"http://purl.org/dc/dcmitype/MovingImage",                                  
                                  "description"=>$acknowledgement . " <br>" . $desc . " <a target='more_info' href='" . $ancestry["taxon_source_url"] . "'>More info.</a><br> <br>" . $location,
                                  "title"=>$desc,
                                  "location"=>$location,
                                  "dc_source"=>$rec["video_url"],
                                  "agent"=>$agent);            

            $arr_sciname["$sciname"]=$sourceURL;
        }
        
                       

        foreach(array_keys($arr_sciname) as $sci)
        {
            $arr_scraped[]=array("id"=>$ctr,
                                 "kingdom"=>$ancestry["kingdom"],   
                                 "phylum"=>$ancestry["phylum"],   
                                 "class"=>$ancestry["class"],   
                                 "order"=>$ancestry["order"],   
                                 "family"=>$ancestry["family"],   
                                 "sciname"=>$sci,
                                 "dc_source"=>$ancestry["taxon_source_url"],   
                                 "photos"=>$arr_photos["$sci"]
                                );
        }        
        //"dc_source"=>$arr_sciname["$sci"],
        
        //print"<pre>";print_r($arr_scraped);print"</pre>"; //debug
        //exit;
        //print"<pre>";print_r($arr_video_info);print"</pre>";
        //exit;
        
        
        return $arr_scraped;        
    }
    

    function get_strings_between_char($string,$delimiter)
    {
        $pos = stripos($string,$delimiter);    
        if(is_numeric($pos))$arr = array(substr($string,0,$pos-1),substr($string,$pos+1,strlen($string)));
        else $arr = array($string,"");        
        return $arr;
    }

    public static function prepare_acknowledgement()    
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $arr = $parser->convert_sheet_to_array("files/NaturalHistoryServices/Acknowledgments.xls");
        $acknowledgement=array();
        $k=0;
        foreach($arr["sciname"] as $sciname)
        {            
            $sci = trim(str_ireplace(".mp4", "", $sciname));
            for ($i=1; $i<=3; $i++)
            {
                if(@$arr["person" . $i][$k])$acknowledgement[$sci][]=@$arr["person" . $i][$k];
            }
            $k++;
        }                
        //print"<pre>";print_r($acknowledgement);print"</pre>";exit;
        return $acknowledgement;
    }
    public static function get_acknowledgement($sciname, $arr)
    {
        //if(!$sciname or $sciname == "" or !@$arr["$sciname"])return;
        if(!@$arr["$sciname"])return;
        
        print"<br>[[$sciname]]";
        
        $acknowledgement="";
        foreach(@$arr["$sciname"] as $person)
        {
            if($acknowledgement) $acknowledgement .= ", " . $person;    
            else $acknowledgement .= $person;    
        }   
        if($acknowledgement) return "Acknowledgments: " . $acknowledgement . "<br>";
        else return;
    }
    

    public static function get_location($arr_video_info,$sciname)
    {
        $location="";
        $i=0;
        foreach($arr_video_info as $rec)
        {            
            if($rec["sciname"] == $sciname && $rec["used"] != 1)
            {
                $location = $rec["locations"];
                $arr_video_info[$i]["used"]=1;                
                return array($location,$arr_video_info);
            }
            
            $i++;
        }    
        //print"<hr><pre>";print_r(array($location,$arr_video_info));print"</pre>";                       
        //exit;
        return array($location,$arr_video_info);
    }
       
    public static function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        
        
        //$taxon["identifier"] = $rec["dc_source"] . "-" . ucfirst(trim($rec["sciname"]));        
        $taxon["identifier"] = "";
        $taxon["source"] = $rec["dc_source"];        
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));       
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));
        
        //$taxon["commonNames"][] = new SchemaCommonName(array("name" => trim($arr[1])));
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", ucfirst(trim($rec["sciname"])), $arr)) $taxon["genus"] = $arr[1];
        
        $photos = $rec["photos"];
        if($photos)
        {
            foreach($photos as $photos)
            {
                $data_object = self::get_data_object($photos);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
            }
        }        
        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    public static function get_data_object($rec)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $rec["identifier"];        
        $data_object_parameters["source"] = $rec["dc_source"];
        
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];
        
        $data_object_parameters["rights"] = "Richard K. Walton - Natural History Services - Online Video";
        $data_object_parameters["rightsHolder"] = "Richard K. Walton";
        
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["location"] = utf8_encode($rec["location"]);
        
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc/3.0/';
        
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
        }
        
        //print_r($rec);
        //print_r(@$rec["agent"]);exit;

         if(@$rec["agent"])
         {               
             $agents = array();
             foreach($rec["agent"] as $a)
             {  
                 $agentParameters = array();
                 $agentParameters["role"]     = $a["role"];
                 $agentParameters["homepage"] = $a["homepage"];
                 $agentParameters["logoURL"]  = "";        
                 $agentParameters["fullName"] = $a["name"];
                 $agents[] = new SchemaAgent($agentParameters);
             }
             $data_object_parameters["agents"] = $agents;
         }
        
        //return new SchemaDataObject($data_object_parameters);
        return $data_object_parameters;
    }    

    public static function clean_html($str)
    {
        $str = str_ireplace('&ldquo;','',$str);//special open quote
        $str = str_ireplace('&rdquo;','',$str);//special end quote
        $str = str_ireplace('&micro;','µ',$str);
        $str = str_ireplace('&mu;' , 'µ', $str);	            
        $str = str_ireplace('&ndash;','-',$str);
        $str = str_ireplace('&deg;' , '°', $str);	        
        $str = str_ireplace('&rsquo;' , "'", $str);	        
        $str = str_ireplace('&gt;' , ">", $str);	        
        $str = str_ireplace('&lt;' , "<", $str);	        
        
        return $str;
    }
    
    public static function clean_str($str)
    {    
        $str = str_ireplace(array("\r", "\t", "\o"), '', $str);			
        $str = str_ireplace(array("\n" ), ' ', $str);			
        
        return $str;
    }

}
?>