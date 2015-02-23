<?php
namespace php_active_record;
/* connector: 511 
Connector makes use of the Soundcloud API to generate the EOL XML.
http://developers.soundcloud.com/docs/api/

There is a Soundcloud group called: Encyclopedia of Life
https://soundcloud.com/groups/encyclopedia-of-life

First step is to get all the members' IDs from the group 'encyclopedia-of-life'.
Second step is to then access/get each member's list of audio using their ID, and from there we generate the EOL XML
*/

class SoundcloudAPI
{
    function __construct()
    {
        $this->soundcloud_api_client_id = "ac6cdf58548a238e00b7892c031378ce";
        $this->EOL_account_user_id = "encyclopedia-of-life";
        $this->EOL_group_id = "60615";
        $this->soundcloud_domain = "http://api.soundcloud.com";
        $this->EOL_members = $this->soundcloud_domain . "/groups/" . $this->EOL_group_id . "/members?client_id=" . $this->soundcloud_api_client_id;
        $this->download_options = array('expire_seconds' => 518400, 'download_wait_time' => 1000000, 'timeout' => 300, 'download_attempts' => 2, 'delay_in_minutes' => 1);
        // since soundcloud is harvested weekly, i've set cache to expire every 6 days = 518400
    }

    function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $user_ids = self::get_list_of_user_ids();
        $count_of_users = count($user_ids);
        $i = 0;
        $options = $this->download_options;
        $options['download_wait_time'] = 3000000;
        foreach($user_ids as $user_id)
        {
            $i++;
            $offset = 0;
            $limit = 50;
            $audio_list_url = $this->soundcloud_domain . "/users/" . $user_id . "/tracks?client_id=" . $this->soundcloud_api_client_id . "&limit=$limit&offset=$offset&downloadable=true";
            debug("\n audio_list_url: " . $audio_list_url);
            $count_of_tracks = 0;
            $page = 1;
            while($page == 1 || $count_of_tracks > 0)
            {
                if($json = Functions::lookup_with_cache($audio_list_url, $options))
                {
                    $tracks = json_decode($json);
                    $count_of_tracks = count($tracks);
                    $j = 0;
                    foreach($tracks as $track)
                    {
                        $j++;
                        debug("\n User $i of $count_of_users (User: [$user_id] - " . $track->user->username . "); page $page Audio $j of $count_of_tracks (trackID: $track->id)");
                        $arr = self::get_soundcloud_taxa($track, $used_collection_ids);
                        $page_taxa              = $arr[0];
                        $used_collection_ids    = $arr[1];
                        if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                    }
                }
                $page++;
                $offset += $limit;
                $audio_list_url = $this->soundcloud_domain . "/users/" . $user_id . "/tracks?client_id=" . $this->soundcloud_api_client_id . "&limit=$limit&offset=$offset&downloadable=true";
            }
        }
        return $all_taxa;
    }

    function get_list_of_user_ids()
    {
        // return array("30860816", "5810611"); // Laura F. [5810611], Eli Agbayani [30860816] , (User: [70505] - Ben Fawkes) has 100+ audio files
        $user_ids = array();
        debug("\n Getting all members... " . $this->EOL_members);
        $offset = 0;
        while(true)
        {
            if($json = Functions::lookup_with_cache($this->EOL_members . "&offset=$offset", $this->download_options))
            {
                $offset += 50; 
                $users = json_decode($json);
                debug("\n members: " . count($users));
                if(!$users) break;
                foreach($users as $user) $user_ids[(string) $user->id] = 1;
            }
            else
            {
                debug("\n Connector terminated. Down: " . $this->EOL_members . "\n");
                return array();
            }
        }
        return array_keys($user_ids);
    }

    function get_soundcloud_taxa($rec, $used_collection_ids)
    {
        $response = $this->parse_xml($rec); //this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["taxon_id"]]) continue;
            $taxon = self::get_taxa_details($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["taxon_id"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    private function parse_xml($rec)
    {
        $license = self::get_cc_license($rec->license);
        if(!$license)
        {
            debug(" - invalid license [$rec->uri]");
            return array();
        }
        if($rec->downloadable != 'true') 
        {
            debug(" - audio not downloadable [$rec->uri]");
            return array();
        }
        $arr_data = array();
        $description = Functions::import_decode($rec->description);
        $arr_sciname = self::get_taxonomy_from_tags($rec->tag_list);
        if(!$arr_sciname)
        {
            $result = self::get_taxonomy_from_description($description);
            $arr_sciname = $result[0];
            $description = $result[1];
        }
        foreach($arr_sciname as $sciname => $temp)
        {
            if(!$sciname && @$arr_sciname[$sciname]['trinomial']) $sciname = @$arr_sciname[$sciname]['trinomial'];
            if(!$sciname && @$arr_sciname[$sciname]['genus'] && @$arr_sciname[$sciname]['species'] && !preg_match("/ /", @$arr_sciname[$sciname]['genus']) && !preg_match("/ /", @$arr_sciname[$sciname]['species'])) $sciname = @$arr_sciname[$sciname]['genus']." ".@$arr_sciname[$sciname]['species'];
            if(!$sciname && !@$arr_sciname[$sciname]['genus'] && !@$arr_sciname[$sciname]['family'] && !@$arr_sciname[$sciname]['order'] && !@$arr_sciname[$sciname]['class'] && !@$arr_sciname[$sciname]['phylum'] && !@$arr_sciname[$sciname]['kingdom']) return array();

            //start data objects //----------------------------------------------------------------------------------------
            $arr_objects  = array();
            $identifier   = $rec->id;
            $dataType     = "http://purl.org/dc/dcmitype/Sound";
            $mimeType     = self::get_mimetype($rec->original_format);
            if(trim($rec->title)) $title = $rec->title;
            else                  $title = "Soundcloud audio";
            $source       = trim($rec->permalink_url);
            $mediaURL     = $rec->download_url . "?client_id=" . $this->soundcloud_api_client_id;
            $thumbnailURL = $rec->waveform_url;
            $agent = array();
            if($rec->user->username) $agent[]= array("role" => "creator", "homepage" => trim($rec->user->permalink_url), "fullName" => trim($rec->user->username));
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects);
            //end data objects //----------------------------------------------------------------------------------------

            $taxon_id   = strtolower(str_ireplace(" ", "_", $sciname));
            $arr_data[] = array("identifier"   => $taxon_id,
                                "source"       => "",
                                "kingdom"      => @$arr_sciname[$sciname]['kingdom'],
                                "phylum"       => @$arr_sciname[$sciname]['phylum'],
                                "class"        => @$arr_sciname[$sciname]['class'],
                                "order"        => @$arr_sciname[$sciname]['order'],
                                "family"       => @$arr_sciname[$sciname]['family'],
                                "genus"        => @$arr_sciname[$sciname]['genus'],
                                "sciname"      => $sciname,
                                "taxon_id"     => $taxon_id,
                                "commonNames"  => @$arr_sciname[$sciname]['commonNames'],
                                "arr_objects"  => $arr_objects
                               );
        }
        if(!$arr_data) debug(" - invalid audio for EOL [$rec->uri]");
        return $arr_data;
    }

    private function get_taxonomy_from_description($description)
    {
        $arr_sciname = array();
        if    (preg_match_all("/\[(.*?)\]/ims", $description, $matches) ||
               preg_match_all("/\((.*?)\)/ims", $description, $matches) ||
               preg_match_all("/\"(.*?)\"/ims", $description, $matches)
              ) //gets everything between brackets [] or parenthesis () or quotes ""
        {
            $smallest_taxa = self::get_smallest_rank($matches[1]);
            $smallest_rank = @$smallest_taxa['rank'];
            $sciname       = @$smallest_taxa['name'];
            $multiple_taxa_YN = self::is_multiple_taxa_video($matches[1]);
            if(!$multiple_taxa_YN) $arr_sciname = self::initialize($sciname);
            foreach($matches[1] as $tag)
            {
                $tag = trim($tag);
                if($multiple_taxa_YN)
                {
                    if(is_numeric(stripos($tag, $smallest_rank)))
                    {
                        if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
                        $arr_sciname = self::initialize($sciname, $arr_sciname);
                    }
                }
                if(preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))       $arr_sciname[$sciname]['binomial']  = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $arr_sciname[$sciname]['trinomial'] = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['genus']     = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['family']    = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['order']     = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['class']     = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['phylum']    = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $arr_sciname[$sciname]['kingdom']   = self::clean_name($arr[1]);
                elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['commonNames'][] = trim($arr[1]);
            }
            foreach($matches[0] as $str) $description = str_ireplace($str, "", trim($description)); //remove brackets or parenthesis in the description
        }
        return array($arr_sciname, $description);
    }

    private function clean_name($string)
    {
        $string = str_ireplace(array("'", '"', "&quot;"), "", $string);
        return trim(ucfirst($string));
    }

    private function get_taxonomy_from_tags($tags)
    {
        $arr_sciname = array();
        if(preg_match_all("/\"(.*?)\"/ims", $tags, $matches))//gets everything between quotation marks "xxx"
        {
            if($smallest_taxa = self::get_smallest_rank($matches[1]))
            {
                $sciname = $smallest_taxa['name'];
                foreach($matches[1] as $tag)
                {
                    $tag = trim($tag);
                    if    (preg_match("/^taxonomy:binomial=(.*)$/i", $tag, $arr))   $arr_sciname[$sciname]['binomial']  = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $tag, $arr))  $arr_sciname[$sciname]['trinomial'] = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:genus=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['genus']     = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:family=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['family']    = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:order=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['order']     = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:class=(.*)$/i", $tag, $arr))      $arr_sciname[$sciname]['class']     = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['phylum']    = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $tag, $arr))    $arr_sciname[$sciname]['kingdom']   = ucfirst(trim($arr[1]));
                    elseif(preg_match("/^taxonomy:common=(.*)$/i", $tag, $arr))     $arr_sciname[$sciname]['commonNames'][] = trim($arr[1]);
                }
            }
        }
        return $arr_sciname;
    }

    function initialize($sciname, $arr_sciname = NULL)
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
        /* [0] => taxonomy:order=Lepidoptera&nbsp;[taxonomy:family=Lymantriidae */
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
        foreach($match as $tag)
        {
            if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
        }
        if(!isset($sciname))
        {
            debug("\n This needs checking... no scientificName found.\n");
            print_r($match);
            return array();
        }
        return array("rank" => $smallest_rank, "name" => self::clean_name($sciname));
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

    function get_taxa_details($rec)
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
        if(@$rec["commonNames"])
        {
            foreach($rec["commonNames"] as $comname)
            {
                $taxon["commonNames"][] = new \SchemaCommonName(array("name" => $comname, "language" => ""));
            }
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
        $data_object_parameters["thumbnailURL"] = trim(@$rec["thumbnailURL"]);
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
            $agentParameters["fullName"] = $agent["fullName"];
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

    function get_mimetype($format)
    {
        /*
        audio/mpeg
        audio/x-ms-wma
        audio/x-pn-realaudio - not allowed in SoundCloud
        audio/x-realaudio - not allowed in SoundCloud
        audio/x-wav
        */
        switch(strtolower($format))
        {
            case 'mp3':
                return 'audio/mpeg'; break;
            case 'wav':
                return 'audio/x-wav'; break;
            case 'raw':
                return 'audio/x-ms-wma'; break;
            default:
                return "";
        }
    }

}
?>