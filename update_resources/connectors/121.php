<?php
/* connector for Hydrothermal Vent Larvae
estimated execution time: 

Connector screen scrapes the partner website.
*/

define("SPECIES_URL", "http://www.whoi.edu/vent-larval-id/");
define("IMAGE_URL", "http://www.whoi.edu/vent-larval-id/");


include_once(dirname(__FILE__) . "/../../config/environment.php");
//require_library('LarvaeAPI');
$GLOBALS['ENV_DEBUG'] = false;

$taxa = LarvaeAPI::get_all_eol_photos();
$xml = SchemaDocument::get_taxon_xml($taxa);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . "1.xml";

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
        $urls = array( 0  => array( "path" => $path . "GastSpecies.htm" , "active" => 0),   //
                       1  => array( "path" => $path . "MiscSpecies.htm" , "active" => 1),   //
                     );
        foreach($urls as $url)
        {
            if($url["active"])
            {
                print $url["path"] . "<br>";
                
                $page_taxa = self::get_larvae_taxa($url["path"]);                
                if($page_taxa)
                {
                    foreach($page_taxa as $t) $all_taxa[] = $t;
                }
            }
        }
        exit;
        return $all_taxa;
    }
    
    public static function get_larvae_taxa($url)
    {
        global $used_collection_ids;
        
        $response = self::search_collections($url);
        $page_taxa = array();
        foreach($response->Collections as $synth)
        {
            if(@$used_collection_ids[$synth->Id]) continue;
            
            $taxon = self::get_taxa_for_photo($synth);
            if($taxon) $page_taxa[] = $taxon;
            
            $used_collection_ids[$synth->Id] = true;
        }
        
        return $page_taxa;
    }
    
    
    public static function search_collections($url)
    {
        $html = Functions::get_remote_file_fake_browser($url);
        $html = self::clean_html($html);
        $arr_url_list = self::get_url_list($html);        
        $response = self::scrape_species_page($arr_url_list);
        exit;
        return $response;//structured array
    }        
    
    public static function clean_html($str)
    {
        $str = str_ireplace("&ldquo;","",$str);//special open quote
        $str = str_ireplace("&rdquo;","",$str);//special end quote
        $str = str_ireplace("&micro;",'µ',$str);//µ        
        $str = str_ireplace('&mu;' , 'µ', $str);	            
        $str = str_ireplace("&ndash;",'-',$str);//µ
        $str = str_ireplace('&deg;' , '°', $str);	        
        $str = str_ireplace('&rsquo;' , "'", $str);	        
        return $str;
    }
    
    public static function get_url_list($str)
    {
        $str = trim(substr($str,stripos($str,"Species Table"),strlen($str)));//start in this section of the HTML
        //print"$str";//exit;
        
        $str = str_ireplace('<a href' , 'xxx<a href', $str);	
        $str = str_ireplace('xxx' , "&arr[]=", $str);	
        $arr = array(); parse_str($str);	    
        //print"<pre>";print_r($arr);print"</pre>";
        print"\n";
        
        $arr_url_list=array();
        foreach($arr as $r)
        {    
            if(!is_numeric(stripos($r,"src=")))
            {
                //special case corrections                    
                $r = str_ireplace('<a name="Arthropods"></a>','',$r);
                $r = str_ireplace('<a name="Miscellaneous"></a>','',$r);
                
                $temp = strip_tags($r,"<a>");                                
                $temp = str_ireplace(' target="_blank"',"",$temp);                
                
                //<a href="Bathymargaritessymplector.htm" target="_blank">Bathymargarites symplector</a>                
                if(preg_match("/\">(.*)<\/a>/", $temp, $matches))$sciname = $matches[1];
                else $sciname="";
                
                if(preg_match("/href=\"(.*)\"/", $temp, $matches))$href = $matches[1];
                else $href="";
                
                //$sciname = self::get_str_from_anchor_tag($temp);
                //$href = self::get_href_from_anchor_tag($temp);                
                
                //print "[[$temp]] [[$sciname]]\n<hr>";                            
                //print "[[$href]] -- [[$sciname]] \n<hr>";                                            

                $url = SPECIES_URL . $href;                                
                
                $arr_url_list[]=array("sciname"=>$sciname,"url"=>$url);

            }            
        }
        print"<pre>";print_r($arr_url_list);print"</pre>";        
        //exit;        
        
        return $arr_url_list;
    }

    public static function scrape_species_page($arr_url_list)
    {
        foreach($arr_url_list as $rec)
        {
            $html = Functions::get_remote_file_fake_browser($rec["url"]);            
            $html = self::clean_html($html);
            
            //print $html;exit;
            //=============================================================================================================
            //start species
            // /*
            $beg='<!-- InstanceBeginEditable name="Species" -->'; $end1='<!-- InstanceEndEditable -->'; 
            $species = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                         
            $species = trim(strip_tags($species));            
            // */
            
            /*
            if(preg_match("/<!-- InstanceBeginEditable name=\"Species\" -->(.*)<!-- InstanceEndEditable -->/", $html, $matches))
            {$species = trim(strip_tags($matches[1]));}
            */
                  
            
            
            $family = self::get_rank("Family",$species);
            $order = self::get_rank("Order",$species);
            $class = self::get_rank("Class",$species);
            
            print"<hr>orig:[[" . $rec["sciname"] . "]] species:[[$species]] family:[[$family]] order:[[$order]] class:[[$class]]<hr>";
            //end species
            //=============================================================================================================
            //start photos

            $beg='<!-- InstanceBeginEditable name="Photos" -->'; $end1='<!-- InstanceEndEditable -->'; 
            $photos = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                               
            
            print"<hr>photos: <Br>";                        
            //http://www.whoi.edu/vent-larval-id/Images/Bathymargarites_symplector-1_web.jpg
            //http://www.whoi.edu/vent-larval-id/Images/Benthic_unknown_A_SEM_web.gif            
            
            $photos = str_ireplace('src="' , '&arr[]=', $photos);	
            $arr = array(); parse_str($photos);	    
            $arr_photos=array();
            foreach($arr as $r)
            {            
                //splits the string with "
                $keywords = preg_split ("/\"/", $r); //print"$keywords[0] <br>";
                if($keywords[0]) $arr_photos[] = IMAGE_URL . $keywords[0];
                //print $keywords[0] . "<hr>";
            }
            print"<pre>";print_r($arr_photos);print"</pre>";            
            //end photos
            //=============================================================================================================
            //start morphology
            $beg = '<!-- InstanceBeginEditable name="Morphology" -->'; $end1='<!-- InstanceEndEditable -->'; 
            $temp = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                            
            
            $beg='Morphology:'; $end1='</td>'; 
            $morphology = trim(self::parse_html($temp,$beg,$end1,$end1,$end1,$end1,''));                               

            print"<hr>morphology: [[$morphology]]";                

            //end morphology
            //=============================================================================================================
            //start confused with

            $beg = '<!-- InstanceBeginEditable name="Confused with" -->'; $end1='<!-- InstanceEndEditable -->'; 
            $temp = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                            
            
            //print"<hr>xxx [[$temp]]";                                
            $confused_with = self::get_confusedWith_desc($temp);
            print"<hr>confused with: [[$confused_with]] ";                

            
            //end confused with
            //=============================================================================================================

            //exit;            
            
        }
        exit;
    }

    public static function get_confusedWith_desc($str)
    {
        //$beg=''; $end1=''; 
        //$temp = trim(self::parse_html($str."xxx",$beg,$end1,$end1,$end1,$end1,''));
        
        $str = str_ireplace('<td' , 'xxx<td', $str);	        
        $str = str_ireplace('xxx' , '&arr[]=', $str);	        
        
        $str = strip_tags($str,"<i><img>");
       
        //$str = utf8_encode($str); 

        /*
        $str = str_ireplace('&deg;' , '°', $str);	        
        $str = str_ireplace('&mu;' , 'µ', $str);	        
        $str = str_ireplace('&rsquo;' , "'", $str);	        
        */
        
        
        $arr = array(); parse_str($str);	    
        //print"<pre>";print_r($arr);print"</pre>";                    

        $str="<table border='1'> ";
        
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
    public static function get_src_from_img_tag($str)
    {
        $beg='src="'; $end1='"';
        $temp = trim(self::parse_html($str,$beg,$end1,$end1,$end1,$end1,"",false));
        return $temp;
    }        
    
    public static function get_taxa_for_photo($synth)
    {
        $tags = self::get_synth_tags($synth);
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;
        foreach($tags as $tag)
        {
            if(preg_match("/^taxonomy:subspecies=(.*)$/i", $tag, $arr))     $taxon["subspecies"] = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $taxon["trinomial"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:species=(.*)$/i", $tag, $arr))    $taxon["species"] = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))   $taxon["scientificName"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $taxon["genus"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $taxon["family"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $taxon["order"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $taxon["class"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $taxon["phylum"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $taxon["kingdom"] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $taxon["commonNames"][] = new SchemaCommonName(array("name" => trim($arr[1])));
            elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr))          $license = strtolower(trim($arr[1]));
        }
        if(!$license) return false;
        if(!in_array($license, array('cc-by', 'cc-by-sa', 'cc-by-nc', 'cc-by-nc-sa', 'public domain')));
        
        if(@!$temp_params["scientificName"] && @$taxon["trinomial"]) $taxon["scientificName"] = $taxon["trinomial"];
        if(@!$temp_params["scientificName"] && @$taxon["genus"] && @$taxon["species"] && !preg_match("/ /", $taxon["genus"]) && !preg_match("/ /", $taxon["species"])) $taxon["scientificName"] = $taxon["genus"]." ".$taxon["species"];
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", $taxon["scientificName"], $arr)) $taxon["genus"] = $arr[1];
        if(@!$taxon["scientificName"] && @!$taxon["genus"] && @!$taxon["family"] && @!$taxon["order"] && @!$taxon["class"] && @!$taxon["phylum"] && @!$taxon["kingdom"]) return false;
        
        
        $data_object = array(self::get_data_object($synth, $license));
        if(!$data_object) return false;
        $taxon["dataObjects"] = $data_object;
        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    public static function get_data_object($synth, $license)
    {
        switch($license)
        {
            case 'cc-by':
                $license = 'http://creativecommons.org/licenses/by/3.0/';
                break;
            case 'cc-by-sa':
                $license = 'http://creativecommons.org/licenses/by-sa/3.0/';
                break;
            case 'cc-by-nc':
                $license = 'http://creativecommons.org/licenses/by-nc/3.0/';
                break;
            case 'cc-by-nc-sa':
                $license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
                break;
            case 'public domain':
                $license = 'http://creativecommons.org/licenses/publicdomain/';
                break;
            default:
              return false;
        }
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $synth->Id;
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["mimeType"] = "image/jpeg";
        $data_object_parameters["title"] = $synth->Name;
        $data_object_parameters["description"] = $synth->Description;
        $data_object_parameters["mediaURL"] = $synth->CollectionUrl;
        $data_object_parameters["thumbnailURL"] = $synth->ThumbnailUrl;
        $data_object_parameters["source"] = COLLECTION_URL . $synth->Id;
        $data_object_parameters["license"] = $license;
        
        $agent_parameters = array();
        $agent_parameters["fullName"] = $synth->OwnerFriendlyName;
        $agent_parameters["homepage"] = USER_URL . $synth->OwnerFriendlyName;
        $agent_parameters["role"] = "photographer";
        $data_object_parameters["agents"] = array();
        $data_object_parameters["agents"][] = new SchemaAgent($agent_parameters);
        
        return new SchemaDataObject($data_object_parameters);
    }
    
    public function get_synth_tags($synth)
    {
        $synth_tags = array();
        $html = Functions::get_remote_file_fake_browser(COLLECTION_URL . $synth->Id);
        if(preg_match("/<div id=\"tagCloud\">(.*?)<\/div>/ims", $html, $arr))
        {
            if(preg_match_all("/aspx\?q=(.*?)\">/", $arr[1], $tags, PREG_SET_ORDER))
            {
                foreach($tags as $tag)
                {
                    $synth_tags[] = Functions::import_decode($tag[1]);
                }
            }
        }
        return $synth_tags;
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