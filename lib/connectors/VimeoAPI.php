<?php

define("VIMEO_USER_SERVICE", "http://vimeo.com/api/v2/");
define("VIMEO_PLAYER_URL", "http://vimeo.com/moogaloop.swf?clip_id=");

class VimeoAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();        

        $users = self::compile_user_list();
        
        $total_users = sizeof($users); $j=0;
        foreach($users as $user)
        {
            $j++;
            $xml = simplexml_load_file(VIMEO_USER_SERVICE . $user . "/videos.xml");                        
            $num_rows = sizeof($xml->video); $i=0;
            foreach($xml->video as $rec)
            {                               
                $i++; print"\n [$j of $total_users] [$i of $num_rows] ";                
                $arr = self::get_vimeo_taxa($rec,$used_collection_ids);                                
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];                            
                if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);                                                                    
            }            
            //break;//debug - just get 1 user.
        }
        return $all_taxa;
    }
    
    public static function get_vimeo_taxa($rec,$used_collection_ids)
    {
        $response = self::parse_xml($rec);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["source"]]) continue;            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;            
            @$used_collection_ids[$rec["source"]] = true;
        }        
        return array($page_taxa,$used_collection_ids);        
    }            
    
    function parse_xml($rec)
    {
        $arr_data=array();                                                
        
        
        $kingdom="";$phylum="";$class="";$order="";$family="";$genus="";$species="";$sciname="";$commonNames=array();$license=null;
        $trinomial="";
        $tags = explode(",", $rec->tags);        
        
        $description = $rec->description;
        if(preg_match_all("/\[(.*?)\]/ims", $description, $matches))//gets everything between brackets []
        {
            foreach($matches[1] as $tag)
            {
                $tag=trim($tag);
                if(preg_match("/^taxonomy:subspecies=(.*)$/i", $tag, $arr))     $subspecies     = strtolower(trim($arr[1]));
                elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $trinomial      = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:species=(.*)$/i", $tag, $arr))    $species        = strtolower(trim($arr[1]));
                elseif(preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))   $sciname        = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $genus          = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $family         = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $order          = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $class          = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $phylum         = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $kingdom        = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $commonNames[]  = trim($arr[1]);
                elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr))          $license        = strtolower(trim($arr[1]));                
            }
            foreach($matches[0] as $str)
            {
                $description = str_ireplace($str,"",$description);
            }
        }                
        
        $description = str_ireplace("<br />","",$description);                
        $description = str_ireplace("&amp;nbsp;","",$description);                
        $description = str_ireplace("&nbsp;","",$description);                                
        $description .= " <a target='vimeo' href='" . $rec->url  . "'>Vimeo</a>";
        
        foreach($tags as $tag)
        {
            $tag=trim($tag);
            if(preg_match("/^taxonomy:subspecies=(.*)$/i", $tag, $arr))     $subspecies     = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $trinomial      = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:species=(.*)$/i", $tag, $arr))    $species        = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))   $sciname        = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $genus          = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $family         = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $order          = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $class          = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $phylum         = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $kingdom        = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $commonNames[]  = trim($arr[1]);
            elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr))          $license        = strtolower(trim($arr[1]));
        }
        if(!$sciname && $trinomial) $sciname = $trinomial;
        if(!$sciname && $genus && $species && !preg_match("/ /", $genus) && !preg_match("/ /", $species)) $sciname = $genus." ".$species;                
        
        //print"\n |$kingdom|$phylum|$class|$order|$family|$genus|$species|$sciname|$license|";//debug
        
        if(!in_array(trim($license), array('cc-by', 'cc-by-sa', 'cc-by-nc', 'cc-by-nc-sa', 'public domain'))) return array();
        $license = self::get_cc_license($license);        
        if(!$sciname && !$genus && !$family && !$order && !$class && !$phylum && !$kingdom) return array();                        
        
        //start data objects //----------------------------------------------------------------------------------------
        $arr_objects=array();        
        $identifier  = $rec->id;
        $dataType    = "http://purl.org/dc/dcmitype/MovingImage"; 
        $mimeType    = "video/x-flv";
        $title       = $rec->title . " [Vimeo]";
        $source      = $rec->url;        
        $mediaURL    = VIMEO_PLAYER_URL . $rec->id;                       
        
        $agent=array();
        if($rec->user_name) $agent = array(0 => array("role" => "creator" , "homepage" => $rec->user_url , $rec->user_name));                    
        $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$arr_objects);
        //end data objects //----------------------------------------------------------------------------------------        
        
        $taxon_id   = str_ireplace(" ","_",$sciname) . "_" . $rec->id;
        
        $arr_data[]=array(  "identifier"   =>$taxon_id,
                            "source"       =>$source,
                            "kingdom"      =>$kingdom,
                            "phylum"       =>$phylum,
                            "class"        =>$class,
                            "order"        =>$order,
                            "family"       =>$family,
                            "genus"        =>$genus,
                            "sciname"      =>$sciname,
                            "commonNames"  =>$commonNames, 
                            "arr_objects"  =>$arr_objects
                         );               
        return $arr_data;        
    }
    
    function add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$arr_objects)
    {
        $arr_objects[]=array( "identifier"=>$identifier,
                              "dataType"=>$dataType,
                              "mimeType"=>$mimeType,
                              "title"=>$title,
                              "source"=>$source,
                              "description"=>$description,
                              "mediaURL"=>$mediaURL,
                              "agent"=>$agent,
                              "license"=>$license
                            );                                    
        return $arr_objects;
    }
    
    function check_xml_if_well_formed($url)
    {
        if(simplexml_load_file($url))return true;     // well-formed XML
        else                         return false;    // not well-formed        
    }
    
    function get_taxa_for_photo($rec)
    {
        $taxon = array();                        
        $taxon["source"] = $rec["source"];
        $taxon["identifier"] = trim($rec["identifier"]);
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));        
        $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));                
        
        //start common names
        foreach($rec["commonNames"] as $comname)
        {            
            $taxon["commonNames"][] = new SchemaCommonName(array("name" => $comname, "language" => ""));
        }
        //end common names                        
                
        if($rec["arr_objects"])
        {
            foreach($rec["arr_objects"] as $object)
            {
                $data_object = self::get_data_object($object);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
            }
        }        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function get_data_object($rec)
    {
        $data_object_parameters = array();        
        $data_object_parameters["identifier"]   = trim(@$rec["identifier"]);        
        $data_object_parameters["source"]       = $rec["source"];        
        $data_object_parameters["dataType"]     = trim($rec["dataType"]);
        $data_object_parameters["mimeType"]     = trim($rec["mimeType"]);
        $data_object_parameters["mediaURL"]     = trim(@$rec["mediaURL"]);        
        $data_object_parameters["created"]      = trim(@$rec["created"]);                
        $data_object_parameters["description"]  = Functions::import_decode(@$rec["description"]);                            
        $data_object_parameters["source"]       = @$rec["source"];        
        $data_object_parameters["license"]      = @$rec["license"];        
        $data_object_parameters["rightsHolder"] = @trim($rec["rightsHolder"]);
        $data_object_parameters["title"]        = @trim($rec["title"]);
        $data_object_parameters["language"]     = "en";
        //==========================================================================================
        $agents = array();
        foreach(@$rec["agent"] as $agent)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";        
            $agentParameters["fullName"] = $agent[0];
            $agents[] = new SchemaAgent($agentParameters);
        }
        $data_object_parameters["agents"] = $agents;    
        //==========================================================================================        
        return $data_object_parameters;
    }    

    function compile_user_list()
    {
        $users=array();
        //$users[]="user1632860"; //Peter Kuttner        
        $users[]="user5352360"; //Eli Agbayani
        return $users;
    }    
    
    function get_cc_license($license)
    {
        switch($license)
        {
            case 'cc-by':
                return 'http://creativecommons.org/licenses/by/3.0/'; break;
            case 'cc-by-sa':
                return 'http://creativecommons.org/licenses/by-sa/3.0/'; break;
            case 'cc-by-nc':
                return 'http://creativecommons.org/licenses/by-nc/3.0/'; break;
            case 'cc-by-nc-sa':
                return 'http://creativecommons.org/licenses/by-nc-sa/3.0/'; break;
            case 'public domain':
                return 'http://creativecommons.org/licenses/publicdomain/'; break;
            default:
                return false;
        }        
    }
            
}
?>