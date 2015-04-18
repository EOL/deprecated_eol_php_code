<?php
namespace php_active_record;
/* connector [121] 
This is a screen-scraping connector. No further development since we harvested their data.
Last run was July 2012 and connector still works. This means no changes in the website page structure.
*/
define("SPECIES_URL", "http://www.whoi.edu/vent-larval-id/");
define("PHOTO_URL", "http://www.whoi.edu/vent-larval-id/");

class HydrothermalVentLarvaeAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        
        $path="http://www.whoi.edu/vent-larval-id/";
        $urls = array(0 => array( "path" => $path . "MiscSpecies.htm", "active" => 1),
                      1 => array( "path" => $path . "GastSpecies.htm", "active" => 1));
        foreach($urls as $url)
        {
            if($url["active"])
            {
                $arr = self::get_larvae_taxa($url["path"], $used_collection_ids);
                if($arr === false) return false;
                $page_taxa = $arr["page_taxa"];
                $used_collection_ids = $arr["used_collection_ids"];
                $all_taxa = array_merge($all_taxa,$page_taxa);
            }
        }
        return $all_taxa;
    }

    public static function get_larvae_taxa($url, $used_collection_ids)
    {
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
        if($response === false) return false;
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            $used_collection_ids[$rec["sciname"]] = true;
        }
        return array("page_taxa" => $page_taxa, "used_collection_ids" => $used_collection_ids);
    }

    public static function search_collections($url)//this will output the raw (but structured) output from the external service
    {
        if(!$html = utf8_decode(Functions::lookup_with_cache($url)))
        {
            echo "\n\n Content partner's server is down, connector will now terminate.\n";
            return false;
        }
        $html = self::clean_html($html);
        $arr_url_list = self::get_url_list($html);
        $response = self::scrape_species_page($arr_url_list);
        if($response === false) return false;
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

        $arr_url_list=array();
        $str = str_ireplace('<tr' , 'xxx<tr', $str);
        $str = str_ireplace('xxx' , "&arr[]=", $str);
        $arr = array(); parse_str($str);
        $loop = 0;
        foreach($arr as $r)
        {
            $r = str_ireplace('<th','<td',$r);
            $r = str_ireplace('</th','</td',$r);
            $r = str_ireplace('<td' , 'xxx<td', $r);
            $r = str_ireplace('xxx' , "&arr2[]=", $r);
            $arr2 = array(); parse_str($r);
            $i = 0;
            foreach($arr2 as $r2)
            {
                if($i == 1)
                {
                    $temp = strip_tags($r2,"<a><em>");
                    $temp = str_ireplace(' target="_blank"',"",$temp);

                    if(preg_match("/<em>(.*)<\/em>/ims", $temp, $matches))$sciname = strip_tags($matches[1]);
                    else $sciname="";                    
                    if(preg_match("/\">(.*?)<\/a>/", $sciname, $matches))$sciname = strip_tags($matches[1]);
                    $sciname = str_ireplace("?","",$sciname);

                    if(preg_match("/href=\"(.*?)\"/", $temp, $matches))$href = $matches[1];
                    else $href="";
                    
                    $url = SPECIES_URL . $href;
                }
                elseif($i == 2)
                {
                    $size = trim(strip_tags($r2));
                    $arr_url_list[]= array("sciname" => $sciname, "url" => $url, "size" => $size);
                    $sciname = ""; $url = ""; $size = ""; //initialize for the next loop
                }
                $i++;
            }
        }

        //unlink blank sciname
        $arr = array();
        foreach($arr_url_list as $url)
        {
            if($url["sciname"])$arr[]=$url;
        }
        $arr_url_list = $arr;
        return $arr_url_list;
    }

    public static function scrape_species_page($arr_url_list)
    {
        $arr_scraped=array();
        $ctr = 0;
        foreach($arr_url_list as $rec)
        {
            $sourceURL = $rec["url"];
            if(!$html = utf8_decode(Functions::lookup_with_cache($sourceURL)))
            {
                echo "\n\n Content partner's server is down, connector will now terminate.\n";
                return false;
            }
            $html = self::clean_html($html);
            $species = "";
            if(preg_match("/<!--\s*InstanceBeginEditable\s*name=\"Species\"\s*-->(.*?)<!--\s*InstanceEndEditable/ims", $html, $matches))
            {$species = trim(strip_tags($matches[1]));}

            if(preg_match("/(.*?)(\.|, Family|Class|Order|Family)/ims", $species, $matches))
            /* starts with any char and ends with "." or ", Family" or ... */

            {$sciname = trim($matches[1]);}
            $sciname = str_ireplace("?","",$sciname);
            $sciname = utf8_encode($sciname);
            $family = self::get_rank("Family",$species);
            $order = self::get_rank("Order",$species);
            $class = self::get_rank("Class",$species);

            //start morphology
            $temp = "";
            if(preg_match("/<!--\s*InstanceBeginEditable\s*name=\"Morphology\"\s*-->(.*?)<!--\s*InstanceEndEditable/ims", $html, $matches)) $temp = trim($matches[1]);

            //get size section
            $size = "";
            if(preg_match("/<td>Size(.*?)<\/td>/ims", $temp, $matches)) $size = "Size " . trim($matches[1]);
            $size = "<table border='0' cellspacing='0' cellpadding='5'><tr><td>" . $size . "</td></tr></table>";
            //end size

            //get just Morphology section
            $morphology = "";
            if(preg_match("/Morphology:(.*?)<\/td>/ims", $temp, $matches))
            {$morphology = trim($matches[1]);}
            $morphology = "<table border='0' cellspacing='0' cellpadding='5'><tr><td>" . strip_tags($morphology,"<tr><td><i>") . "</td></tr></table>";
            //end morphology

            //start photos
            $photos = "";
            if(preg_match("/<!--\s*InstanceBeginEditable\s*name=\"Photos\"\s*-->(.*?)<!--\s*InstanceEndEditable/ims", $html, $matches)) $photos_main = $matches[1];            
            $photos = str_ireplace('src="', '&arr[]=', $photos_main); $arr = array(); parse_str($photos);
            $photos2 = str_ireplace('onClick="', '&arr2[]=', $photos_main); $arr2 = array(); parse_str($photos2);
            
            $arr_photos = array();
            $i = 0;
            foreach($arr as $r)
            {
                $keywords = preg_split ("/\"/", $r);
                if($keywords[0])
                {
                    $img = trim($keywords[0]);
                    if(is_numeric(stripos($img,".gif"))) $mimeType="image/gif";
                    if(is_numeric(stripos($img,".jpg"))) $mimeType="image/jpeg";

                    $agent = array();
                    if(is_numeric(stripos(@$arr2[$i],"_SEM_")))
                    {                        
                         $agent[] = array("role" => "photographer", "homepage" => "http://www.whoi.edu/", "name" => "Susan Mills");
                         $agent[] = array("role" => "photographer", "homepage" => "http://www.whoi.edu/", "name" => "Diane Adams");
                    }
                    else $agent[] = array("role" => "photographer", "homepage" => "http://www.whoi.edu/", "name" => "Stace Beaulieu");
                    $arr_photos[] = array("mediaURL" => PHOTO_URL . $keywords[0], "mimeType" => $mimeType, "dataType" => "http://purl.org/dc/dcmitype/StillImage",
                                          "description" => $morphology, "dc_source" => $sourceURL, "agent" => $agent);
                }
                $i++;
            }
            //end photos
            
            //start confused with
            $beg = '<!-- InstanceBeginEditable name="Confused with" -->'; $end1='<!-- InstanceEndEditable -->';
            $temp = self::parse_html($html,$beg,$end1,$end1,$end1,$end1,'');                            
            $confused_with = self::get_confusedWith_desc($temp);
            //end confused with

            //text object agents
            $agent = array();
            $agent[] = array("role" => "author", "homepage" => "http://www.whoi.edu/", "name" => "S.W. Mills");
            $agent[] = array("role" => "author", "homepage" => "http://www.whoi.edu/", "name" => "S.E. Beaulieu");
            $agent[] = array("role" => "author", "homepage" => "http://www.whoi.edu/", "name" => "L.S. Mullineaux");
            $ctr++;
            $arr_scraped[]=array("id" => $ctr,
                                 "sciname"    => $sciname,
                                 "family"     => $family,
                                 "order"      => $order,
                                 "class"      => $class,
                                 "dc_source"  => $sourceURL,
                                 "morphology" => array("description" => $morphology   ,"subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size","title"=>"Morphology","dataType"=>"http://purl.org/dc/dcmitype/Text","dc_source" => $sourceURL,"agent" => $agent),
                                 "lookalikes" => array("description" => $confused_with,"subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#LookAlikes","title"=>"Can be confused with:","dataType"=>"http://purl.org/dc/dcmitype/Text","dc_source" => $sourceURL,"agent" => $agent),
                                 "size"       => array("description" => $size         ,"subject"=>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Size","title"=>"","dataType"=>"http://purl.org/dc/dcmitype/Text","dc_source" => $sourceURL,"agent" => $agent),
                                 "photos"     => $arr_photos
                                );
        }
        return $arr_scraped;
    }

    public static function get_confusedWith_desc($str)
    {
        $str = str_ireplace('<td' , 'xxx<td', $str);
        $str = str_ireplace('xxx' , '&arr[]=', $str);
        $str = strip_tags($str,"<i><img>");
        $arr = array(); parse_str($str);
        if(!$arr) return;
        $str = "<table border='0' cellspacing='0' cellpadding='5'>";
        $i = 0;
        foreach($arr as $r)
        {
            $i++;
            if($i % 2==1)$str.="<tr>";
            if(trim($r) != "" && !preg_match("/See Notes/", $r))
            {
                if($img = self::get_src_from_img_tag($r))
                {    
                    $r = "<img src='" . PHOTO_URL . $img . "'>";
                }                
                $str .= "<td>$r</td>";
            }            
            if($i % 2==0) $str .= "</tr>";
        }
        $str .= "</table>";
        if($str == "<table border='0' cellspacing='0' cellpadding='5'><tr></table>") $str = "";
        return $str;
    }

    public static function get_rank($rank, $str)
    {
        $beg = $rank; $end1 = 'xxx'; 
        $temp = trim(self::parse_html($str."xxx", $beg, $end1, $end1, $end1, $end1, ''));
        $keywords = preg_split ("/\./", $temp);//split it by .
        $rank = $keywords[0];
        return $rank;
    }

    public static function get_src_from_img_tag($str)
    {
        $beg ='src="'; $end1 ='"';
        $temp = trim(self::parse_html($str, $beg, $end1, $end1, $end1, $end1, "", false));
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
        if(@!$taxon["genus"] && @preg_match("/^([^ ]+) /", ucfirst(trim($rec["sciname"])), $arr)) $taxon["genus"] = $arr[1];

        $arr = $rec["morphology"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
        }
        $arr = $rec["lookalikes"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
        }
        $arr = $rec["size"];
        if($arr["description"])
        {
            $data_object = self::get_data_object($arr);
            if(!$data_object) return false;
            $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
        }

        $photos = $rec["photos"];
        if($photos)
        {
            foreach($photos as $photos)
            {
                $data_object = self::get_data_object($photos);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
            }
        }

        $taxon_object = new \SchemaTaxon($taxon);
        return $taxon_object;
    }

    public static function get_data_object($rec)
    {
        $data_object_parameters = array();
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
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }
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
                 $agents[] = new \SchemaAgent($agentParameters);
             }
             $data_object_parameters["agents"] = $agents;
         }
        return $data_object_parameters;
    }

     public static function parse_html($str, $beg, $end1, $end2, $end3, $end4, $all=NULL, $exit_on_first_match = false) //str = the html block
     {
         $beg_len = strlen(trim($beg));
         $end1_len = strlen(trim($end1));
         $end2_len = strlen(trim($end2));
         $end3_len = strlen(trim($end3));
         $end4_len = strlen(trim($end4));

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
                 $cont = 'y';
                 while($cont == 'y')
                 {
                     if( strtolower(substr($str,$i,$end1_len)) == strtolower($end1) or
                         strtolower(substr($str,$i,$end2_len)) == strtolower($end2) or
                         strtolower(substr($str,$i,$end3_len)) == strtolower($end3) or
                         strtolower(substr($str,$i,$end4_len)) == strtolower($end4) or
                         substr($str,$i,3) == '|||' )
                     {
                         $pos2 = $i - 1;
                         $cont = 'n';
                         $arr[$k] = substr($str,$pos1,$pos2-$pos1+1);
                         $k++;
                     }
                     $i++;
                 }//end while
                 $i--;
                 if($exit_on_first_match) break;
             }
         }//end outer loop
         if($all == "")
         {
             $id = '';
             for ($j = 0; $j < count($arr); $j++){$id = $arr[$j];}
             return $id;
         }
         elseif($all == "all") return $arr;
    }
}
?>
