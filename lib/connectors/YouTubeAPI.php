<?php
namespace php_active_record;
/* connector: 323 */

define("DEVELOPER_KEY", "AI39si4JyuxT-aemiIm9JxeiFbr4F3hphhrhR1n3qPkvbCrrLRohUbBSA7ngDqku8mUGEAhYZpKDTfq2tu_mDPImDAggk8At5Q");
define("YOUTUBE_EOL_USER", "EncyclopediaOfLife");
define("YOUTUBE_API", "http://gdata.youtube.com/feeds/api");

class YouTubeAPI
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
            $j++; 
            $num_rows = count($user["video_ids"]);
            $i = 0;
            foreach($user["video_ids"] as $video_id)
            {
                $record = self::build_data($video_id);
                $i++; print "\n [user $j of $total_users] [video $i of $num_rows] ";
                if($record) 
                {
                    $arr = self::get_youtube_taxa($record, $used_collection_ids);
                    $page_taxa              = $arr[0];
                    $used_collection_ids    = $arr[1];
                    if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                }
            }
        }
        return $all_taxa;
    }

    public static function get_youtube_taxa($record, $used_collection_ids)
    {
        $response = self::parse_xml($record);//this will output the raw (but structured) array
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

    function build_data($video_id)
    {
        //special case
        if(substr($video_id, 0, 1) == "-") $video_id = "/" . trim(substr($video_id, 1, strlen($video_id)));
        
        $video = array();
        $url = YOUTUBE_API  . '/videos?q=' . $video_id . '&license=cc&v=2';
        print "\n $url";
        $xml = Functions::get_hashed_response($url);
        if($xml->entry) print " -- valid license";
        else print ' -- invalid license';
        foreach($xml->entry as $e)
        {
            $e_media = $e->children("http://search.yahoo.com/mrss/");
            foreach($e_media->group->thumbnail[0]->attributes() as $field => $value) if($field == 'url') $thumbnail = $value; 
            foreach($e_media->group->content[0]->attributes() as $field => $value) if($field == 'url') $mediaURL = $value; 
            $video = array(  "id"            => $url,
                             "author"        => trim($e->author->name),
                             "author_uri"    => trim($e->author->uri),
                             "author_detail" => $e->author->uri, //get_author_detail(trim($e->author->uri)),
                             "author_url"    => "http://www.youtube.com/user/" . trim($e->author->name),
                             "media_title"   => trim($e_media->group->title),
                             "description"   => trim($e_media->group->description),
                             "thumbnail"     => $thumbnail,
                             "sourceURL"     => 'http://youtu.be/' . $video_id,
                             "mediaURL"      => $mediaURL,
                             "video_id"      => $video_id
                            );
        }
        return $video;
    }

    function parse_xml($rec)
    {
        $arr_data = array();
        $description = Functions::import_decode($rec['description']);
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
            }
            foreach($matches[0] as $str) $description = str_ireplace($str, "", trim($description));
        }

        $license = 'http://creativecommons.org/licenses/by/3.0/';

        foreach($arr_sciname as $sciname => $temp)
        {
            if(!$sciname && @$arr_sciname[$sciname]['trinomial']) $sciname = @$arr_sciname[$sciname]['trinomial'];
            if(!$sciname && @$arr_sciname[$sciname]['genus'] && @$arr_sciname[$sciname]['species'] && !preg_match("/ /", @$arr_sciname[$sciname]['genus']) && !preg_match("/ /", @$arr_sciname[$sciname]['species'])) $sciname = @$arr_sciname[$sciname]['genus']." ".@$arr_sciname[$sciname]['species'];                        
            if(!$sciname && !@$arr_sciname[$sciname]['genus'] && !@$arr_sciname[$sciname]['family'] && !@$arr_sciname[$sciname]['order'] && !@$arr_sciname[$sciname]['class'] && !@$arr_sciname[$sciname]['phylum'] && !@$arr_sciname[$sciname]['kingdom']) return array();
                        
            //start data objects //----------------------------------------------------------------------------------------
            $arr_objects = array();
            $identifier  = $rec['id'];
            $dataType    = "http://purl.org/dc/dcmitype/MovingImage";
            $mimeType    = "video/x-flv";
            if(trim($rec['media_title'])) $title = $rec['media_title'];
            else                          $title = "YouTube video";
            $source       = $rec['sourceURL'];
            $mediaURL     = $rec['mediaURL'];
            $thumbnailURL = $rec['thumbnail'];
            $agent = array();
            if($rec['author']) $agent[] = array("role" => "author" , "homepage" => $rec['author_url'] , $rec['author']);
            if(stripos($description, "<br>Author: ") == "")
            {
                $description .= "<br><br>Author: <a href='$rec[author_url]'>$rec[author]</a>";
                $description .= "<br>Source: <a href='$rec[sourceURL]'>YouTube</a>";
            }
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects);
            //end data objects //----------------------------------------------------------------------------------------

            $taxon_id   = str_ireplace(" ", "_", $sciname) . '_' . $rec['video_id'];
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
        if($rec["sciname"] != @$rec["family"]) $taxon["family"] = ucfirst(trim(@$rec["family"]));
        if($rec["sciname"] != @$rec["genus"]) $taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        if($rec["sciname"] != @$rec["order"]) $taxon["order"] = ucfirst(trim(@$rec["order"]));
        if($rec["sciname"] != @$rec["class"]) $taxon["class"] = ucfirst(trim(@$rec["class"]));
        if($rec["sciname"] != @$rec["phylum"]) $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        if($rec["sciname"] != @$rec["kingdom"]) $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));
        foreach($rec["commonNames"] as $comname) $taxon["commonNames"][] = new \SchemaCommonName(array("name" => $comname, "language" => ""));
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
        return $data_object_parameters;
    }

    function compile_user_list()
    {
        $users = array();
        /* you can add users by adding them here
        $users["ile1731"] = ""; 
        $users["ile173"] = ""; 
        */
        $users['EncyclopediaOfLife'] = 1;
        
        /* as of 3-14-12: This is the same list that is taken from the API below. 
        This is just a safeguard that when the API suddenly changes that EOL won't lose all their YouTube contributors */
        $users['jenhammock1']        = 1;
        $users['PRI']                = 1;
        $users['treegrow']           = 1;
        $users['soapberrybug']       = 1;
        $users['heliam']             = 1;
        $users['smithsonianNMNH']    = 1;
        $users['robmutch1']          = 1;
        $users['NESCentMedia']       = 1;
        $users['TuftsEnvStudies']    = 1;
        $users['censusofmarinelife'] = 1;
        $users['lubaro1977']         = 1;

        /* or you can get them by getting all the subscriptions of the YouTube user 'EncyclopediaOfLife' */
        $url = YOUTUBE_API . '/users/' . YOUTUBE_EOL_USER . '/subscriptions?v=2';
        print "\n $url \n";
        $xml = Functions::get_hashed_response($url);
        foreach($xml->entry as $entry)
        {
            foreach($entry->title as $title)
            {
                $arr = explode(":", $title); //explode string -- 'Activity of : {user_id}'
                $id = trim($arr[1]);
                $users[$id] = 1;
            }
        }

        $users = self::assign_video_ids(array_keys($users));
        return $users;
    }

    function assign_video_ids($user_ids)
    {
        $users = array();
        /* We need to excluded a number of YouTube users because they have many videos and none of which is for EOL and each of those videos is checked by the connector. */
        $exclude_users = array('PRI');
        $user_ids = array_diff(array_values($user_ids), $exclude_users);
        foreach($user_ids as $user_id)
        {
            $start_index = 1;
            $max_results = 25;
            $video_ids = array();
            while(true)
            {
                $url = YOUTUBE_API . '/users/' . $user_id . '/uploads';
                $url .= "?start-index=$start_index&max-results=$max_results";
                print "\n $url";
                $xml = Functions::get_hashed_response($url);
                if($xml->entry)
                {
                    foreach($xml->entry as $entry) 
                    {
                        print "\n $entry->id";
                        $path_parts = pathinfo($entry->id);
                        $video_ids[] = $path_parts['basename'];
                    }
                }
                else break;
                $start_index += $max_results;
            }
            print "\n";
            $users[$user_id]["id"] = $user_id;
            $users[$user_id]["video_ids"] = $video_ids;
        }
        return $users;
    }

}
?>