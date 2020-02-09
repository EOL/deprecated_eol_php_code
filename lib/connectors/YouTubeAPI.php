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

/* old API key
define("DEVELOPER_KEY", "AI39si4JyuxT-aemiIm9JxeiFbr4F3hphhrhR1n3qPkvbCrrLRohUbBSA7ngDqku8mUGEAhYZpKDTfq2tu_mDPImDAggk8At5Q");
*/
/* Old API key. Replaced on Feb 9, 2020
AIzaSyCXt2WPrcQniaMomonEruEOi3EHYlGEi3U
*/
/* Google Developers project name: EOL Connectors
   Public API access: API key: YOUR_API_KEY

get subscriptions:
https://www.googleapis.com/youtube/v3/subscriptions?channelId=UCECuihlM1FFpO2lONWqY8gA&part=snippet,id,subscriberSnippet&key=YOUR_API_KEY

get playlist_id using channel_id
https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=UCECuihlM1FFpO2lONWqY8gA&key={YOUR_API_KEY}

from username to videolist to video details
https://www.googleapis.com/youtube/v3/channels?part=snippet,contentDetails,statistics,status&forUsername=EncyclopediaOfLife&key=YOUR_API_KEY
https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails,status&playlistId=UUECuihlM1FFpO2lONWqY8gA&key=YOUR_API_KEY
https://www.googleapis.com/youtube/v3/videos?part=snippet&id=_Foofq1fhYY&key=YOUR_API_KEY
*/

define("DEVELOPER_KEY", $GLOBALS['GOOGLE_DEV_API_KEY']);
define("YOUTUBE_EOL_USER", "EncyclopediaOfLife");
define("YOUTUBE_API", "http://gdata.youtube.com/feeds/api");
define("YOUTUBE_API_V3", "https://www.googleapis.com/youtube/v3");
define("TAXON_FINDER_SERVICE", "http://www.ubio.org/webservices/service.php?function=taxonFinder&freeText=");

class YouTubeAPI
{
    function __construct()
    {
        // cache expires after 30 days; download timeout is 2 minutes; download interval is 2 seconds
        $this->download_options = array('resource_id' => 323, 'expire_seconds' => 2592000, 'download_wait_time' => 2000000, 'timeout' => 120, 'download_attempts' => 1); //, 'delay_in_minutes' => 5
        // $this->download_options['expire_seconds'] = false; //false - doesn't expire | 2592000 - expires in 30 days
    }
    
    public function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $usernames_of_subscribers = self::get_subscriber_usernames_for_v3();
        $user_video_ids = self::get_upload_videos_from_usernames($usernames_of_subscribers);
        $total_users = count($usernames_of_subscribers);
        $user_index = 0;
        foreach(array_keys($usernames_of_subscribers) as $username)
        {
            $user_index++;
            if(@!$user_video_ids[$username]) continue;
            $number_of_user_videos = count($user_video_ids[$username]);
            $video_index = 0;
            foreach($user_video_ids[$username] as $video)
            {
                // echo "\n $username - " . $video->contentDetails->videoId;
                $video_index++;
                // echo " [user $user_index of $total_users] [video $video_index of $number_of_user_videos]";
                if($record = self::build_data($video, $username))
                {
                    $record["username"] = $username; // not used at the moment
                    $arr = self::get_youtube_taxa($record, $used_collection_ids);
                    $page_taxa              = $arr[0];
                    $used_collection_ids    = $arr[1];
                    if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                }
                // break; //debug
            }
            // break; //debug
        }
        return $all_taxa;
    }

    public function get_youtube_taxa($record, $used_collection_ids)
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

    public function build_data($video, $username) //username here is actually the channel_id
    {
        // $url = YOUTUBE_API  . '/videos/' . $video_id . '?v=3&alt=json';
        // $tries = 0;
        // while($tries < 5)
        // {
        //     if($raw_json = Functions::lookup_with_cache($url, $this->download_options))
        //     {
        //         if(is_numeric(stripos($raw_json, "too_many_recent_calls")))
        //         {
        //             debug(" Failed due to 'too many recent calls'. Will retry in 30 seconds.");
        //             sleep(30);
        //             $tries += 1;
        //         }else break;
        //     }else
        //     {
        //         debug(" - Fail. Will retry in 30 seconds.");
        //         sleep(30);
        //         $tries += 1;
        //     }
        // }

        $video_id = $video->contentDetails->videoId;
        $license = $video->status->privacyStatus;

        // For a while we used the API URL for the identifier (not sure why). Just
        // trying to preserve that so I don't lose all curation/rating information
        // for the existing objects. Really we just need to use the video ID: -ravHVw8K4U
        // video IDs starting with '-' must have the - tuened into /
        // eg: -ravHVw8K4U becomes /ravHVw8K4U
        $identifier_video_id = $video_id;
        if(substr($identifier_video_id, 0, 1) == "-") $identifier_video_id = "/" . trim(substr($identifier_video_id, 1));
        return array("id"            => YOUTUBE_API  . '/videos?q=' . $identifier_video_id . '&license=cc&v=3',
                     "author"        => $video->snippet->channelTitle, //self::get_author_name($video->snippet->channelId),
                     "author_uri"    => '',
                     "author_detail" => '',
                     "author_url"    => "https://www.youtube.com/channel/" . $username,
                     "media_title"   => $video->snippet->title,
                     "description"   => str_replace("\r\n", "<br/>", trim($video->snippet->description)),
                     "thumbnail"     => $video->snippet->thumbnails->medium->url,
                     "sourceURL"     => 'http://youtu.be/' . $video_id,
                     "mediaURL"      => "http://www.youtube.com/embed/" . $video_id,
                     "video_id"      => $video_id);
    }

    private function get_author_name($channel_id) //not used at the moment
    {
        $url = YOUTUBE_API_V3 . "/channels.list?id=" . $channel_id . "&part=snippet,contentDetails&key=" . DEVELOPER_KEY;
    }
    
    private function parse_xml($rec)
    {
        $arr_data = array();
        $description = Functions::import_decode($rec['description']);
        $description = str_ireplace("<br />", "", $description);
        $description = str_ireplace("taxonomy:binomial-", "taxonomy:binomial=", $description);
        $description = str_ireplace("taxonomy:Subfamily", "taxonomy:subfamily", $description);
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
                $tag = trim($tag);
                if($multiple_taxa_YN)
                {
                    if(is_numeric(stripos($tag, $smallest_rank)))
                    {
                        if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
                        $arr_sciname = self::initialize($sciname, $arr_sciname);
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
        
        /*commented since Ubio service is offline
        if(!$arr_sciname) // probably no machine tags, let us check names inside the title using Ubio API
        {
            if($scinames = self::get_sciname(array($rec["media_title"], $rec["description"])))
            {
                foreach($scinames as $sciname)
                {
                    $arr_sciname = self::initialize($sciname, $arr_sciname);
                    $arr_sciname[$sciname]['binomial'] = $sciname; // this may not always be a binomial but it's ok as it will end up as dwc:ScientificName
                }
            }
        }
        */
        
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

    private function get_sciname($strings_to_search) //Ubio search
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 15552000; //six months before it expires
    
        $scinames = array();
        foreach($strings_to_search as $string)
        {
            if(!$string = trim($string)) continue;
            $url = TAXON_FINDER_SERVICE . urlencode($string);
            if($response = Functions::lookup_with_cache($url, $options)) //1hr timeout
            {
                $response = simplexml_load_string($response);
                if(isset($response->allNames->entity))
                {
                    foreach($response->allNames->entity as $entity)
                    {
                        $sciname = (string) $entity->nameString;
                        $taxon_id = (string) $entity->namebankID;
                        $scinames[] = $sciname;
                    }
                    // if($scinames) print_r($scinames); //peak/look-see at what Ubio's TaxoFinder gives us.
                }
            }
            if($scinames) break; // if you get names in title, no need to search on description anymore
        }
        return $scinames;
    }

    private function initialize($sciname, $arr_sciname=NULL)
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

    private function is_multiple_taxa_video($arr)
    {
        $taxa = array();
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

    private function get_smallest_rank($match)
    {
        $rank_id = array("trinomial" => 1, "binomial" => 2, "genus" => 3, "subfamily" => 4, "family" => 5, "order" => 6, "class" => 7, "phylum" => 8, "division" => 9, "kingdom" => 10);
        $smallest_rank_id = 11;
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
            // echo "\n This needs checking..."; print_r($match); //debug - uncomment when developing...
            $sciname = "";
        }
        return array("rank" => $smallest_rank, "name" => $sciname);
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects)
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

    private function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["source"] = $rec["source"];
        $taxon["identifier"] = trim($rec["identifier"]);
        $taxon["scientificName"]                                    = self::format_name($rec["sciname"]);
        if($rec["sciname"] != @$rec["family"]) $taxon["family"]     = self::format_name(@$rec["family"]);
        if($rec["sciname"] != @$rec["genus"]) $taxon["genus"]       = self::format_name(@$rec["genus"]);
        if($rec["sciname"] != @$rec["order"]) $taxon["order"]       = self::format_name(@$rec["order"]);
        if($rec["sciname"] != @$rec["class"]) $taxon["class"]       = self::format_name(@$rec["class"]);
        if($rec["sciname"] != @$rec["phylum"]) $taxon["phylum"]     = self::format_name(@$rec["phylum"]);
        if($rec["sciname"] != @$rec["kingdom"]) $taxon["kingdom"]   = self::format_name(@$rec["kingdom"]);
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
    
    private function format_name($str)
    {
        $str = ucfirst(trim($str));
        return str_replace('"', '', $str);
    }

    private function get_data_object($rec)
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

    private function get_subscriber_usernames_for_v3() //no more usernames in V3. this is getting 
    {
        /* We need to excluded a number of YouTube users because they have many videos and none of which is for EOL and each of those videos is checked by the connector. */
        $exclude_this_channel = array('PRI Public Radio International');
        
        $eol_channel_id = 'UCECuihlM1FFpO2lONWqY8gA';
        $max_results = 10;
        $page_token = false;
        while(true)
        {
            $url = YOUTUBE_API_V3 . "/subscriptions?part=snippet&channelId=" . $eol_channel_id . "&key=" . DEVELOPER_KEY . "&maxResults=$max_results";
            if($page_token) $url .= "&pageToken=" . $page_token;
            if($raw_json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $json = json_decode($raw_json);
                if($val = @$json->nextPageToken) $page_token = $val;
                else                             $page_token = false;
                
                if($json->items)
                {
                    foreach($json->items as $items)
                    {
                        if(in_array($items->snippet->title, $exclude_this_channel))
                        {
                            echo "\nexcluded: " . $items->snippet->title . "\n";
                            continue;
                        }
                        $user_channel_ids[$items->snippet->title] = $items->snippet->resourceId->channelId;
                        $channel_playlist_ids[$items->snippet->resourceId->channelId] = self::get_playlist_id_using_channel_id($items->snippet->resourceId->channelId);
                        
                    }
                }else break;
            }else break;
            if(!$page_token) break;
        }

        // print_r($user_channel_ids);        echo "\ntotal: " . count($user_channel_ids);
        // print_r($channel_playlist_ids);    echo "\ntotal: " . count($channel_playlist_ids);
        return $channel_playlist_ids;
    }
    
    private function get_subscriber_usernames()
    {
        // return array("sheshadriali" => self::get_playlist_id('sheshadriali')); //debug

        $usernames_of_subscribers = array();
        $usernames_of_subscribers['EncyclopediaOfLife'] = self::get_playlist_id('EncyclopediaOfLife');
        
        /* We need to excluded a number of YouTube users because they have many videos and none of which is for EOL and each of those videos is checked by the connector. */
        $usernames_of_people_to_ignore = array('PRI', 'pri');
        /* Getting all the subscriptions of the YouTube user 'EncyclopediaOfLife' */
        $start_index = 1;
        $max_results = 20;
        while(true)
        {    
            echo "\n Getting subscriptions...";
            $url = YOUTUBE_API . '/users/' . YOUTUBE_EOL_USER . '/subscriptions?v=2' . "&start-index=$start_index&max-results=$max_results";
            if($xml = Functions::lookup_with_cache($url, $this->download_options))
            {
                $xml = simplexml_load_string($xml);
                if($xml->entry)
                {
                    foreach($xml->entry as $entry)
                    {
                        $yt = $entry->children("http://gdata.youtube.com/schemas/2007");
                        $username = trim($yt->username);
                        if(!in_array($username, $usernames_of_people_to_ignore)) $usernames_of_subscribers[$username] = self::get_playlist_id($username);
                    }
                }
                else break;
                $start_index += $max_results;
            }
            else break;
            // break; //debug
        }
        
        return $usernames_of_subscribers;
    }

    private function get_playlist_id_using_channel_id($channel_id)
    {
        $url = YOUTUBE_API_V3 . "/channels?part=contentDetails&id=" . $channel_id . "&key=" . DEVELOPER_KEY;
        if($raw_json = Functions::lookup_with_cache($url, $this->download_options))
        {
            $json = json_decode($raw_json);
            if($val = @$json->items[0]->contentDetails->relatedPlaylists->uploads) return $val;
            else return false; 
        }
    }
    
    private function get_playlist_id($username)
    {
        $url = YOUTUBE_API_V3 . "/channels?part=snippet,contentDetails,statistics,status&forUsername=" . $username . "&key=" . DEVELOPER_KEY;
        if($raw_json = Functions::lookup_with_cache($url, $this->download_options))
        {
            $json = json_decode($raw_json);
            if($val = @$json->items[0]->contentDetails->relatedPlaylists->uploads) return $val;
            else return false; 
        }
    }

    public function get_upload_videos_from_usernames($usernames)
    {
        $max_results = 20;
        $user_video_ids = array();
        foreach($usernames as $username => $playlist_id)
        {
            if(!$playlist_id) continue;
            echo "\n Getting video list for $username...";
            $page_token = false;
            while(true)
            {
                $url = YOUTUBE_API_V3 . "/playlistItems?part=snippet,contentDetails,status&playlistId=" . $playlist_id . "&key=" . DEVELOPER_KEY . "&maxResults=$max_results";
                if($page_token) $url .= "&pageToken=" . $page_token;
                if($raw_json = Functions::lookup_with_cache($url, $this->download_options))
                {
                    $words = array("taxonomy", "trinomial", "binomial", "genus", "subfamily", "family", "order", "class", "phylum", "division", "kingdom");
                    foreach($words as $word) $raw_json = str_ireplace($word, strtolower($word), $raw_json);
                    
                    $raw_json = str_ireplace("taxonomy: binomial", "taxonomy:binomial", $raw_json);
                    $raw_json = str_ireplace("taxonomy:binomial:", "taxonomy:binomial=", $raw_json);
                    $json = json_decode($raw_json);
                    if($val = @$json->nextPageToken) $page_token = $val;
                    else                             $page_token = false;
                    if($json->items)
                    {
                        // echo "\n" . count($json->items) . "\n";
                        foreach($json->items as $items) $user_video_ids[$username][] = $items;
                    }else break;
                }else break;
                if(!$page_token) break;
            }
            // break; //debug
        }
        return $user_video_ids;
    }

}
?>