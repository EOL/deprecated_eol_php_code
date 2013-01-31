<?php
namespace php_active_record;
/* connector: 323
There is a YouTube user called 'EncyclopediaOfLife'.
This user (EncyclopediaOfLife) will subscribe to any user who will want to share their videos to EOL.
Then using the API we can get all users willing to share their videos and their videos' details.
We can then ingest these videos into EOL.

So what does a user need to do to have his YouTube video(s) show up in EOL.
Edit their video information:
Step 1: Set the license of the video(s) to "Creative Commons Attribution license (reuse allowed)".
Step 2: In the description section, add the taxon name after the description.
Step 3: The YouTube user should then let SPG know that he is now ready to share.
SPG, who owns the user 'EncyclopediaOfLife' would then subscribe to that user.

This YouTube connector who will then process user 'EncyclopediaOfLife'.
- get its subscriptions (users who are wiling to share videos to EOL)
- filter the cc videos for each of these users
- ingest it into EOL
- end

For step 2 above:
How to add a taxon name: for example species Anarhichas lupus
Can be this long:
[taxonomy:binomial=Anarhichas lupus]
[taxonomy:kingdom=Animalia]
[taxonomy:phylum=Chordata]
[taxonomy:class=Actinopterygii]
[taxonomy:order=Perciformes]
[taxonomy:family=Anarhichadidae]
[taxonomy:common=Atlantic wolffish]

Can be this simple:
[taxonomy:binomial=Anarhichas lupus]
[taxonomy:family=Anarhichadidae]

Or just simply:
[taxonomy:binomial=Anarhichas lupus]
*/

define("DEVELOPER_KEY", "AI39si4JyuxT-aemiIm9JxeiFbr4F3hphhrhR1n3qPkvbCrrLRohUbBSA7ngDqku8mUGEAhYZpKDTfq2tu_mDPImDAggk8At5Q");
define("YOUTUBE_EOL_USER", "EncyclopediaOfLife");
define("YOUTUBE_API", "http://gdata.youtube.com/feeds/api");

class YouTubeAPI
{
    public static function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $usernames_of_subscribers = self::get_subscriber_usernames();
        $user_video_ids = self::get_upload_videos_from_usernames($usernames_of_subscribers);
        $total_users = count($usernames_of_subscribers);
        $user_index = 0;
        foreach($usernames_of_subscribers as $username)
        {
            $user_index++;
            if(@!$user_video_ids[$username]) continue;
            $number_of_user_videos = count($user_video_ids[$username]);
            $video_index = 0;
            foreach($user_video_ids[$username] as $video_id)
            {
                debug("\n $username - $video_id");
                $video_index++;
                debug(" [user $user_index of $total_users] [video $video_index of $number_of_user_videos]");
                if($record = self::build_data($video_id, $username))
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
        //this will output the raw (but structured) array
        $response = self::parse_xml($record);
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

    public static function build_data($video_id, $username)
    {
        $url = YOUTUBE_API  . '/videos/' . $video_id . '?v=2&alt=json';
        $tries = 0;
        while($tries < 5)
        {
            if($raw_json = Functions::get_remote_file($url, DOWNLOAD_WAIT_TIME, 120, 5))
            {
                if(is_numeric(stripos($raw_json, "too_many_recent_calls")))
                {
                    debug(" Failed due to 'too many recent calls'. Will retry in 30 seconds.");
                    sleep(30);
                    $tries += 1;
                }else break;
            }else
            {
                debug(" - Fail. Will retry in 30 seconds.");
                sleep(30);
                $tries += 1;
            }
        }
        $raw_json = str_ireplace("taxonomy", "taxonomy", $raw_json);
        $raw_json = str_ireplace("binomial", "binomial", $raw_json);
        $raw_json = str_ireplace("taxonomy: binomial", "taxonomy:binomial", $raw_json);
        $raw_json = str_ireplace("taxonomy:binomial:", "taxonomy:binomial=", $raw_json);
        $json_object = json_decode($raw_json);
        if(!@$json_object->entry->id)
        {
            debug(" -- invalid response");
            return;
        }
        $license = @$json_object->entry->{'media$group'}->{'media$license'}->href;
        if(!$license || !preg_match("/^http:\/\/creativecommons.org\/licenses\//", $license))
        {
            debug(" -- invalid response");
            return;
        }
        $thumbnailURL = @$json_object->entry->{'media$group'}->{'media$thumbnail'}[1]->url;
        $mediaURL = @$json_object->entry->{'media$group'}->{'media$content'}[0]->url;

        // For a while we used the API URL for the identifier (not sure why). Just
        // trying to preserve that so I don't lose all curation/rating information
        // for the existing objects. Really we just need to use the video ID: -ravHVw8K4U
        // video IDs starting with '-' must have the - tuened into /
        // eg: -ravHVw8K4U becomes /ravHVw8K4U
        $identifier_video_id = $video_id;
        if(substr($identifier_video_id, 0, 1) == "-") $identifier_video_id = "/" . trim(substr($identifier_video_id, 1));
        return array("id"            => YOUTUBE_API  . '/videos?q=' . $identifier_video_id . '&license=cc&v=2',
                     "author"        => $json_object->entry->author[0]->name->{'$t'},
                     "author_uri"    => $json_object->entry->author[0]->uri->{'$t'},
                     "author_detail" => $json_object->entry->author[0]->uri->{'$t'},
                     "author_url"    => "http://www.youtube.com/user/" . $username,
                     "media_title"   => $json_object->entry->title->{'$t'},
                     "description"   => str_replace("\r\n", "<br/>", trim($json_object->entry->{'media$group'}->{'media$description'}->{'$t'})),
                     "thumbnail"     => $json_object->entry->{'media$group'}->{'media$thumbnail'}[1]->url,
                     "sourceURL"     => 'http://youtu.be/' . $video_id,
                     "mediaURL"      => $json_object->entry->{'media$group'}->{'media$content'}[0]->url,
                     "video_id"      => $video_id );
    }

    private static function parse_xml($rec)
    {
        $arr_data = array();
        $description = Functions::import_decode($rec['description']);
        $description = str_ireplace("<br />", "", $description);
        $license = "";
        $arr_sciname = array();
        if(preg_match_all("/\[(.*?)\]/ims", $description, $matches)) //gets everything between brackets []
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
                        if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
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
            //start data objects
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
            //end data objects
            $taxon_id   = str_ireplace(" ", "_", $sciname) . '_' . $rec['video_id'];
            $arr_data[] = array("identifier"   => "",
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

    private static function initialize($sciname, $arr_sciname=NULL)
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

    private static function is_multiple_taxa_video($arr)
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

    private static function get_smallest_rank($match)
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
            debug("\n This needs checking...");
            print_r($match);
        }
        return array("rank" => $smallest_rank, "name" => $sciname);
    }

    private static function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects)
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

    private static function get_taxa_for_photo($rec)
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

    private static function get_data_object($rec)
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

    private static function get_subscriber_usernames()
    {
        $usernames_of_subscribers = array();
        $usernames_of_subscribers['EncyclopediaOfLife'] = 1;
        /* We need to excluded a number of YouTube users because they have many videos and none of which is for EOL and each of those videos is checked by the connector. */
        $usernames_of_people_to_ignore = array('PRI', 'pri');
        /* Getting all the subscriptions of the YouTube user 'EncyclopediaOfLife' */
        $url = YOUTUBE_API . '/users/' . YOUTUBE_EOL_USER . '/subscriptions?v=2';
        if($xml = Functions::get_hashed_response($url, 1000000, 240, 5))
        {
            foreach($xml->entry as $entry)
            {
                $yt = $entry->children("http://gdata.youtube.com/schemas/2007");
                $username = trim($yt->username);
                if(!in_array($username, $usernames_of_people_to_ignore)) $usernames_of_subscribers[$username] = 1;
            }
        }
        else debug("\n Service not available: $url");
        return array_keys($usernames_of_subscribers);
    }

    public static function get_upload_videos_from_usernames($usernames)
    {
        $max_results = 50;
        $user_video_ids = array();
        foreach($usernames as $username)
        {
            debug("\n Getting video list for $username...");
            $start_index = 1;
            while(true)
            {
                $url = YOUTUBE_API . "/users/" . $username . "/uploads?" . "start-index=$start_index&max-results=$max_results";
                if($xml = Functions::get_hashed_response($url, 3000000, 240, 5))
                {
                    if($xml->entry)
                    {
                        foreach($xml->entry as $entry)
                        {
                            $user_video_pathinfo = pathinfo($entry->id);
                            $user_video_ids[$username][] = $user_video_pathinfo['basename'];
                        }
                    }else break; //no more videos, go to next user
                    $start_index += $max_results;
                }else break; //five (5) un-successful tries already, go to next user, hopefully it doesn't go here
            }
        }
        return $user_video_ids;
    }

}
?>