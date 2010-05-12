<?php
/* connector for Hydrothermal Vent Larvae
estimated execution time: 

Connector screen scrapes the partner website.
*/

define("SPECIES_URL", "http://www.whoi.edu/vent-larval-id/");
define("IMAGE_URL", "http://www.whoi.edu/vent-larval-id/");

include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

$taxa = LarvaeAPI::get_all_eol_photos();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "121.xml";

$OUT = fopen($resource_path, "w+");
fwrite($OUT, $xml);
fclose($OUT);

echo "time: ". Functions::time_elapsed()."\n";
?>


<?php

class LarvaeAPI
{
    public static function get_all_eol_photos()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        
        $path="http://www.whoi.edu/vent-larval-id/";
        $urls = array( 0  => array( "path" => $path . "MiscSpecies.htm" , "active" => 1),  //
                       1  => array( "path" => $path . "GastSpecies.htm" , "active" => 1)   //                       
                     );
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $page_taxa = self::get_larvae_taxa($url["path"]);                                
                print"<hr>website: " . $url["path"] . "<br>";
                print"page_taxa count: " . $url["path"] . " -- " . count($page_taxa) . "<hr>";
                
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
        print"<hr><pre>all_taxa: ";print_r($all_taxa);print"</pre>";        
        print"total: " . count($all_taxa);        
        return $all_taxa;
    }
    
    public static function get_larvae_taxa($url)
    {
        global $used_collection_ids;
        
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
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
    
    public static function search_collections($url)//this will output the raw (but structured) output from the external service
    {
        $html = Functions::get_remote_file_fake_browser($url);
        $html = self::clean_html($html);
        $arr_url_list = self::get_url_list($html);        
        $response = self::scrape_species_page($arr_url_list);
        return $response;//structured array
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
    
    public static function get_url_list($str)
    {
        $str = trim(substr($str,stripos($str,"Species Table"),strlen($str)));//start in this section of the HTML
        //special case corrections                    
        $str = str_ireplace('<a name="Arthropods"></a>','',$str);
        $str = str_ireplace('<a name="Miscellaneous"></a>','',$str);

        //print"$str";//exit;
             

        $arr_url_list=array();
        
        $str = str_ireplace('<tr' , 'xxx<tr', $str);	
        $str = str_ireplace('xxx' , "&arr[]=", $str);	
        $arr = array(); parse_str($str);	    
        //print"<pre>";print_r($arr);print"</pre>";
        
        $loop=0;//debug
        foreach($arr as $r)
        {
            //$loop++;if($loop >= 4)break;
                    
            $r = str_ireplace('<th','<td',$r);
            $r = str_ireplace('</th','</td',$r);
        
            $r = str_ireplace('<td' , 'xxx<td', $r);	
            $r = str_ireplace('xxx' , "&arr2[]=", $r);	
            $arr2 = array(); parse_str($r);	    
            //print"<pre>";print_r($arr2);print"</pre>"; exit;

            $i=0;
            foreach($arr2 as $r2)
            {
                if($i==1)
                {
                    $temp = strip_tags($r2,"<a><em>");                                
                    $temp = str_ireplace(' target="_blank"',"",$temp);                
                    
                    //<a href="Bathymargaritessymplector.htm" target="_blank">Bathymargarites symplector</a>                
                    //if(preg_match("/\">(.*?)<\/a>/", $temp, $matches))$sciname = utf8_encode($matches[1]);
                    
                    if(preg_match("/<em>(.*)<\/em>/ims", $temp, $matches))$sciname = strip_tags(utf8_encode($matches[1]));
                    else $sciname="";
                    
                    if(preg_match("/\">(.*?)<\/a>/", $sciname, $matches))$sciname = strip_tags($matches[1]);
                    //else $sciname="";
                    
                    $sciname = str_ireplace("?","",$sciname);                    
                    
                    if(preg_match("/href=\"(.*?)\"/", $temp, $matches))$href = $matches[1];
                    else $href="";
                    
                    $url = SPECIES_URL . $href;                                                    
    
                }
                elseif($i==2)
                {
                    $size = trim(strip_tags($r2));                    
                    $arr_url_list[]=array("sciname"=>$sciname,"url"=>$url,"size"=>$size);                    
                    $sciname=""; $url=""; $size=""; //initialize for the next loop                    
                }                
                
                $i++;    
            }                           
        }        
        
        print"<pre>";print_r($arr_url_list);print"</pre>";        
        
        //unlink blank sciname
        $arr=array();
        foreach($arr_url_list as $url)
        {
            if($url["sciname"])$arr[]=$url;
        }
        print"<pre>";print_r($arr);print"</pre>";        
        $arr_url_list=$arr;
        
        //exit;                
        return $arr_url_list;
    }

    public static function scrape_species_page($arr_url_list)
    {
        $arr_scraped=array();
        $ctr=0;
        foreach($arr_url_list as $rec)
        {
            $sourceURL=$rec["url"];
            $html = Functions::get_remote_file_fake_browser($sourceURL);            
            $html = self::clean_html($html);
            
            //print $html;exit;
            //=============================================================================================================
            //start species
            /*
            $beg='<!-- InstanceBeginEditable name="Species" -->'; $end1='<!-- InstanceEndEditable -->'; 
            $species = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                         
            $species = trim(strip_tags($species));            
            */
             
            /*
            Per PL:
            - use \s* for spaces in your pattern.
            - put ? in here: (.*?) if you want your pattern to be not greedy, without ? will be greedy.
            */ 
            
            $species="";
            if(preg_match("/<!--\s*InstanceBeginEditable\s*name=\"Species\"\s*-->(.*?)<!--\s*InstanceEndEditable/ims", $html, $matches))
            {$species = trim(strip_tags($matches[1]));}
            
            $sciname = $rec["sciname"];
            $family = self::get_rank("Family",$species);
            $order = self::get_rank("Order",$species);
            $class = self::get_rank("Class",$species);
            
            //print"<hr>orig:[[" . $sciname . "]] species:[[$species]] family:[[$family]] order:[[$order]] class:[[$class]]<hr>";
            //end species

            //=============================================================================================================
            //start morphology
            /* $beg = '<!-- InstanceBeginEditable name="Morphology" -->'; $end1='<!-- InstanceEndEditable -->'; */            
            
            $temp="";
            if(preg_match("/<!--\s*InstanceBeginEditable\s*name=\"Morphology\"\s*-->(.*?)<!--\s*InstanceEndEditable/ims", $html, $matches))
            {$temp = trim($matches[1]);}            
            
            /* get entire table
            $morphology = "<table border='0' cellspacing='0' cellpadding='5'>" . strip_tags($temp,"<tr><td><i>") . "</table>";
            */

            //get just Morphology section
            /* $beg='Morphology:'; $end1='</td>'; */

            $morphology="";
            if(preg_match("/Morphology:(.*?)<\/td>/ims", $temp, $matches))
            {$morphology = trim($matches[1]);}
            $morphology = "<table border='0' cellspacing='0' cellpadding='5'>" . strip_tags($morphology,"<tr><td><i>") . "</table>";

            print"<hr>morphology: [[$morphology]]";                

            //end morphology

            //=============================================================================================================
            //start photos
            /* $beg='<!-- InstanceBeginEditable name="Photos" -->'; $end1='<!-- InstanceEndEditable -->'; */
            
            $photos="";
            if(preg_match("/<!--\s*InstanceBeginEditable\s*name=\"Photos\"\s*-->(.*?)<!--\s*InstanceEndEditable/ims", $html, $matches))
            {$photos = $matches[1];}            

            //print"<hr>photos: [[$photos]]<Br>"; exit;
            //http://www.whoi.edu/vent-larval-id/Images/Bathymargarites_symplector-1_web.jpg
            //http://www.whoi.edu/vent-larval-id/Images/Benthic_unknown_A_SEM_web.gif            
            
            $photos = str_ireplace('src="' , '&arr[]=', $photos);	
            $arr = array(); parse_str($photos);	    
            $arr_photos=array();
            foreach($arr as $r)
            {            
                //splits the string with "
                $keywords = preg_split ("/\"/", $r); //print"$keywords[0] <br>";
                if($keywords[0])
                { 
                    $img = trim($keywords[0]);
                    if(substr($img,strlen($img)-3,3)=="gif")$mimeType="image/gif";
                    if(substr($img,strlen($img)-3,3)=="jpg")$mimeType="image/jpeg";                    
                    
                    $agent=array();
                    if(is_numeric(stripos($img,"_SEM_")))
                    {                        
                         $agent[]=array("role" => "photographer" , "homepage" => "http://www.whoi.edu/" , "name" => "Susan Mills");
                         $agent[]=array("role" => "photographer" , "homepage" => "http://www.whoi.edu/" , "name" => "Diane Adams");
                    }
                    else $agent[]=array("role" => "photographer" , "homepage" => "http://www.whoi.edu/" , "name" => "Stace Beaulieu");
                    $arr_photos[] = array("mediaURL"=>IMAGE_URL . $keywords[0],"mimeType"=>$mimeType,"dataType"=>"http://purl.org/dc/dcmitype/StillImage","description"=>$morphology,"dc_source"=>$sourceURL,"agent"=>$agent);
                }
                //print $keywords[0] . "<hr>";
            }
            //print"<pre>";print_r($arr_photos);print"</pre>"; //debug            
            //end photos
            //=============================================================================================================
            
            //start confused with
            $beg = '<!-- InstanceBeginEditable name="Confused with" -->'; $end1='<!-- InstanceEndEditable -->'; 
            $temp = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                            
            
            //print"<hr>xxx [[$temp]]";                                
            $confused_with = self::get_confusedWith_desc($temp);
            //print"<hr>confused with: [[$confused_with]] ";                
            
            //end confused with
            //=============================================================================================================
            
            
            $ctr++;
            $arr_scraped[]=array("id"=>$ctr,
                                 "sciname"=>$sciname,
                                 "family"=>$family,
                                 "order"=>$order,
                                 "class"=>$class,
                                 "dc_source"=>$sourceURL,
                                 "morphology"=>array("description"=>$morphology   ,"subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Morphology","title"=>"","dataType"=>"http://purl.org/dc/dcmitype/Text","dc_source"=>$sourceURL),
                                 "lookalikes"=>array("description"=>$confused_with,"subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#LookAlikes","title"=>"Can be confused with:","dataType"=>"http://purl.org/dc/dcmitype/Text","dc_source"=>$sourceURL),
                                 "size"=>array("description"=>"Size: " . $rec["size"],"subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size","title"=>"","dataType"=>"http://purl.org/dc/dcmitype/Text","dc_source"=>$sourceURL),
                                 "photos"=>$arr_photos
                                );
                                
            
            //"photos"=>$arr_photos,

        }
        //print"<pre>";print_r($arr_scraped);print"</pre>"; //debug
        return $arr_scraped;
        
    }

    public static function get_confusedWith_desc($str)
    {
        //$beg=''; $end1=''; 
        //$temp = trim(self::parse_html($str."xxx",$beg,$end1,$end1,$end1,$end1,''));
        
        $str = str_ireplace('<td' , 'xxx<td', $str);	        
        $str = str_ireplace('xxx' , '&arr[]=', $str);	        
        
        $str = strip_tags($str,"<i><img>");
        
        $arr = array(); parse_str($str);	    
        if(!$arr)return;
        
        //print"<pre>";print_r($arr);print"</pre>";                    

        $str="<table border='0' cellspacing='0' cellpadding='5'>";
        
        $i=0;
        foreach($arr as $r)
        {   
            $i++;
            if($i % 2==1)$str.="<tr>";            

            if(trim($r) != "" and !preg_match("/See Notes/", $r)) 
            {
                if($img = self::get_src_from_img_tag($r)) 
                {    
                    $r = "<img src='" . IMAGE_URL . $img . "'>";
                }                
                $str .= "<td>$r</td>";                
                //print "<hr>$i $r";            
            }
            
            if($i % 2==0)$str.="</tr>";
        }
        $str.="</table>";        
        if($str=="<table border='0' cellspacing='0' cellpadding='5'><tr></table>")$str="";        
        //print $str;exit;        
        return $str;
    }

    public static function get_rank($rank,$str)
    {
        $beg=$rank; $end1='xxx'; 
        $temp = trim(self::parse_html($str."xxx",$beg,$end1,$end1,$end1,$end1,''));
        $keywords = preg_split ("/\./", $temp);//split it by .
        $rank = $keywords[0];
        return $rank;
    }
        
    /*
    public static function get_href_from_anchor_tag($str)
    {
        $beg='href="'; $end1='"';
        $temp = trim(self::parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));
        return $temp;
    }
    public static function get_str_from_anchor_tag($str)
    {
        $beg='">'; $end1='</a>';
        $temp = trim(self::parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));
        return $temp;
    }        
    */
    public static function get_src_from_img_tag($str)
    {
        $beg='src="'; $end1='"';
        $temp = trim(self::parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));
        return $temp;
    }        
    
    public static function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        
        $taxon["source"] = $rec["dc_source"];
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));
        //$taxon["commonNames"][] = new SchemaCommonName(array("name" => trim($arr[1])));
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", $taxon["scientificName"], $arr)) $taxon["genus"] = $arr[1];
        
        $arr = $rec["morphology"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
        }        
        $arr = $rec["lookalikes"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
        }
        $arr = $rec["size"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
        }
                
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
        //$data_object_parameters["identifier"] = $synth->Id;        
        
        $data_object_parameters["source"] = $rec["dc_source"];
        
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];
        
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["source"] = @$rec["sourceURL"];
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
        
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
    
    
     public static function parse_html($str,$beg,$end1,$end2,$end3,$end4,$all=NULL,$exit_on_first_match=false) //str = the html block
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
                     if(    strtolower(substr($str,$i,$end1_len)) == strtolower($end1) or
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
    
}
?>