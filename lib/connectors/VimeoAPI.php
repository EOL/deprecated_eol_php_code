<?php
namespace php_active_record;
/* connector: 214 */
define("VIMEO_USER_SERVICE", "http://vimeo.com/api/v2/");
define("VIMEO_PLAYER_URL", "http://vimeo.com/moogaloop.swf?clip_id=");

class VimeoAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $users = self::compile_user_list();
        $total_users = sizeof($users); 
        $j = 0;
        foreach($users as $user)
        {
            $j++; print "\n";
            $num_rows = count($user["video_ids"]);
            $i = 0;
            foreach($user["video_ids"] as $video_id)
            {
                $xml = Functions::get_hashed_response(VIMEO_USER_SERVICE . "video/" . trim($video_id) . ".xml");
                print "\n" . VIMEO_USER_SERVICE . "video/" . trim($video_id) . ".xml";
                if(sizeof($xml->video)) 
                {
                    $i++; print "\n [user $j of $total_users] [video $i of $num_rows] ";
                    $arr = self::get_vimeo_taxa($xml->video, $used_collection_ids);
                    $page_taxa              = $arr[0];
                    $used_collection_ids    = $arr[1];
                    if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                }
            }
        }
        return $all_taxa;
    }

    public static function get_vimeo_taxa($rec, $used_collection_ids)
    {
        $response = self::parse_xml($rec);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["taxon_id"]]) continue;
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["taxon_id"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    function parse_xml($rec)
    {
        $arr_data=array();
        $description = Functions::import_decode($rec->description);
        $description = str_ireplace("<br />", "", $description);

        $license = "";
        $arr_sciname = array();
        if(preg_match_all("/\[(.*?)\]/ims", $description, $matches))//gets everything between brackets []
        {
            $smallest_taxa = self::get_smallest_rank($matches[1]);
            $smallest_rank = $smallest_taxa['rank'];
            $sciname       = $smallest_taxa['name'];
            //smallest rank sciname: [$smallest_rank][$sciname]
            $multiple_taxa_YN = self::is_multiple_taxa_video($matches[1]);
            if(!$multiple_taxa_YN) $arr_sciname = self::initialize($sciname);
            foreach($matches[1] as $tag)
            {
                $tag=trim($tag);
                if($multiple_taxa_YN)
                {
                    if(is_numeric(stripos($tag,$smallest_rank)))
                    {
                        if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr))$sciname = ucfirst(trim($arr[1]));
                        $arr_sciname = self::initialize($sciname,$arr_sciname);
                    }
                }
                if(preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))       $arr_sciname[$sciname]['binomial']  = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $arr_sciname[$sciname]['trinomial'] = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['genus']     = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['family']    = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['order']     = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['class']     = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['phylum']    = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $arr_sciname[$sciname]['kingdom']   = ucfirst(trim($arr[1]));
                elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['commonNames'][]  = trim($arr[1]);
                elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr))          $license = strtolower(trim($arr[1]));
            }
            foreach($matches[0] as $str) $description = str_ireplace($str, "", trim($description));
        }

        $with_eol_tag = false;
        $tags = explode(",", $rec->tags);
        foreach($tags as $tag)
        {
            $tag = trim($tag);
            if(trim($tag) == "eol") $with_eol_tag = true;
            elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr)) $license = strtolower(trim($arr[1]));
        }

        /* Not a pre-requisite anymore
        //has to have an 'eol' tag
        if(!$with_eol_tag) return array();
        */

        //license from Vimeo license settings - scraped from the video page
        $license = self::get_license_from_page($rec->url);

        if(!$license)
        {
            //license from Vimeo tag or description section
            $license = self::get_cc_license($license);
        }

        //has to have a valid license
        if(!$license)
        {
            print "\n invalid license [$rec->url]";
            return array();
        }

        foreach($arr_sciname as $sciname => $temp)
        {
            if(!$sciname && @$arr_sciname[$sciname]['trinomial']) $sciname = @$arr_sciname[$sciname]['trinomial'];
            if(!$sciname && @$arr_sciname[$sciname]['genus'] && @$arr_sciname[$sciname]['species'] && !preg_match("/ /", @$arr_sciname[$sciname]['genus']) && !preg_match("/ /", @$arr_sciname[$sciname]['species'])) $sciname = @$arr_sciname[$sciname]['genus']." ".@$arr_sciname[$sciname]['species'];                        
            if(!$sciname && !@$arr_sciname[$sciname]['genus'] && !@$arr_sciname[$sciname]['family'] && !@$arr_sciname[$sciname]['order'] && !@$arr_sciname[$sciname]['class'] && !@$arr_sciname[$sciname]['phylum'] && !@$arr_sciname[$sciname]['kingdom']) return array();
                        
            //start data objects //----------------------------------------------------------------------------------------
            $arr_objects = array();
            $identifier  = $rec->id;
            $dataType    = "http://purl.org/dc/dcmitype/MovingImage";
            $mimeType    = "video/x-flv";
            if(trim($rec->title)) $title = $rec->title;
            else                  $title = "Vimeo video";
            $source      = $rec->url;
            $mediaURL    = VIMEO_PLAYER_URL . $rec->id;
            $thumbnailURL = $rec->thumbnail_large;
            $agent = array();
            if($rec->user_name) $agent = array(0 => array("role" => "creator" , "homepage" => $rec->user_url , $rec->user_name));
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects);
            //end data objects //----------------------------------------------------------------------------------------

            $taxon_id   = str_ireplace(" ", "_", $sciname) . "_" . $rec->id;
            $arr_data[]=array(  "identifier"   => "",
                                "source"       => "",
                                "kingdom"      => $arr_sciname[$sciname]['kingdom'],
                                "phylum"       => $arr_sciname[$sciname]['phylum'],
                                "class"        => $arr_sciname[$sciname]['class'],
                                "order"        => $arr_sciname[$sciname]['order'],
                                "family"       => $arr_sciname[$sciname]['family'],
                                "genus"        => $arr_sciname[$sciname]['genus'],
                                "sciname"      => $sciname,
                                "taxon_id"     => $taxon_id,
                                "commonNames"  => @$arr_sciname[$sciname]['commonNames'],
                                "arr_objects"  => $arr_objects
                             );
        }
        return $arr_data;
    }

    function initialize($sciname, $arr_sciname=NULL)
    {
        $arr_sciname[$sciname]['binomial']    = "";
        $arr_sciname[$sciname]['trinomial']   = "";
        $arr_sciname[$sciname]['subspecies']  = "";
        $arr_sciname[$sciname]['species']     = "";
        $arr_sciname[$sciname]['genus']       = "";
        $arr_sciname[$sciname]['family']      = "";
        $arr_sciname[$sciname]['order']       = "";
        $arr_sciname[$sciname]['class']       = "";
        $arr_sciname[$sciname]['phylum']      = "";
        $arr_sciname[$sciname]['kingdom']     = "";
        $arr_sciname[$sciname]['commonNames'] = array();
        return $arr_sciname;
    }

    function is_multiple_taxa_video($arr)
    {
        $taxa=array();
        foreach($arr as $tag)
        {
            if(preg_match("/^taxonomy:(.*)\=/i", $tag, $arr))
            {
                $rank = trim($arr[1]);
                if(in_array($rank,$taxa)) return 1;
                $taxa[] = $rank;
            }
        }
        return 0;
    }

    function get_smallest_rank($match)
    {
        /*
          [0] => taxonomy:order=Lepidoptera&nbsp;[taxonomy:family=Lymantriidae
        */

        $rank_id = array("trinomial" => 1, "binomial" => 2, "genus" => 3, "family" => 4, "order" => 5, "class" => 6, "phylum" => 7, "kingdom" => 8);
        $smallest_rank_id = 9;
        $smallest_rank = "";
        foreach($match as $tag)
        {
            if(preg_match("/^taxonomy:(.*)\=/i", $tag, $arr))
            {
                $rank = trim($arr[1]);
                if(in_array($rank, array_keys($rank_id)))
                {
                    if($rank_id[$rank] < $smallest_rank_id)
                    {
                        $smallest_rank_id = $rank_id[$rank];
                        $smallest_rank = $rank;
                    }
                }
            }
        }
        foreach($match as $tag) if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
        if(!isset($sciname))
        {
            print "\n This needs checking...";
            print "<pre>"; print_r($match); print "</pre>";
        }
        return array("rank" => $smallest_rank, "name" => $sciname);
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects)
    {
        $arr_objects[] = array( "identifier"   => $identifier,
                                "dataType"     => $dataType,
                                "mimeType"     => $mimeType,
                                "title"        => $title,
                                "source"       => $source,
                                "description"  => $description,
                                "mediaURL"     => $mediaURL,
                                "agent"        => $agent,
                                "license"      => $license,
                                "thumbnailURL" => $thumbnailURL
                              );
        return $arr_objects;
    }

    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["source"] = $rec["source"];
        $taxon["identifier"] = trim($rec["identifier"]);
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));

        if($rec["sciname"]!=@$rec["family"])$taxon["family"] = ucfirst(trim(@$rec["family"]));
        if($rec["sciname"]!=@$rec["genus"])$taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        if($rec["sciname"]!=@$rec["order"])$taxon["order"] = ucfirst(trim(@$rec["order"]));
        if($rec["sciname"]!=@$rec["class"])$taxon["class"] = ucfirst(trim(@$rec["class"]));
        if($rec["sciname"]!=@$rec["phylum"])$taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        if($rec["sciname"]!=@$rec["kingdom"])$taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));

        foreach($rec["commonNames"] as $comname)
        {
            $taxon["commonNames"][] = new \SchemaCommonName(array("name" => $comname, "language" => ""));
        }

        if($rec["arr_objects"])
        {
            foreach($rec["arr_objects"] as $object)
            {
                $data_object = self::get_data_object($object);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
            }
        }
        $taxon_object = new \SchemaTaxon($taxon);
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
        $data_object_parameters["thumbnailURL"]     = trim(@$rec["thumbnailURL"]);
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
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $data_object_parameters["agents"] = $agents;
        //==========================================================================================
        return $data_object_parameters;
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
            case 'by-sa':
                return 'http://creativecommons.org/licenses/by-sa/3.0/'; break;
            case 'by-nc':
                return 'http://creativecommons.org/licenses/by-nc/3.0/'; break;
            case 'by-nc-sa':
                return 'http://creativecommons.org/licenses/by-nc-sa/3.0/'; break;
            default:
                return false;
        }
    }

    function get_license_from_page($video_page_url)
    {
        $html = Functions::get_remote_file($video_page_url);
        if(preg_match("/<a href=\"http:\/\/creativecommons.org\/licenses\/(.*?)\//ims", $html, $matches)) return self::get_cc_license("cc-" . trim($matches[1]));
        return false;
    }

    function compile_user_list()
    {
        $users = array();
        /* you can add users by adding them here especially if they haven't join the EOL-Vimeo group:
        $users["user1632860"] = ""; //Peter Kuttner
        $users["user5361059"] = ""; //Patrick Leary
        $users["morphologic"] = ""; //morphologic
        $users["user6136460"] = ""; //Ximena Miranda
        $users["user1632860"] = ""; //Peter Kuttner
        $users["user5814509"] = ""; //Katja Schulz
        $users["user5352360"] = ""; //Eli Agbayani
        */

        /* or you can include them by getting all the members from the EOL-Vimeo group */
        $xml = Functions::get_hashed_response(VIMEO_USER_SERVICE . "group/encyclopediaoflife/users.xml");
        foreach($xml->user as $user)
        {
            $path_parts = pathinfo($user->profile_url);
            $user = $path_parts['filename'];
            $users[$user] = "";
        }

        $users = self::assign_video_ids(array_keys($users));
        return $users;
    }

    function assign_video_ids($user_ids)
    {
        include_once(dirname(__FILE__) . "/../../vendor/vimeo/vimeo.php");
        $vimeo = new \phpVimeo('4dd12148ba83e2f6e0ad3483a0eae9ef', 'aa7c2d4015aafb25');
        $users = array();
        foreach($user_ids as $user_id)
        {
            $page = 1;
            $video_ids = array();
            while(true)
            {
                $all_videos = $vimeo->call('vimeo.videos.getUploaded', array('user_id' => $user_id, 'page' => $page));
                $page++;
                if(isset($all_videos->videos->video)) foreach($all_videos->videos->video as $video) $video_ids[] = $video->id;
                else
                {
                    print "\n not yet setup: user_id:[$user_id] -- no. of pages:[$page]";
                    break;
                }
            }
            $users[$user_id]["id"] = $user_id;
            $users[$user_id]["video_ids"] = $video_ids;
        }
        return $users;
    }

}
?>