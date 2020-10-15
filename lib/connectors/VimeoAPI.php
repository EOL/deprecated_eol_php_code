<?php
namespace php_active_record;
require_once DOC_ROOT . '/vendor/vimeo-vimeo-php-lib-a32ff71/vimeo.php';

/* connector: 214 
Connector makes use of the Advanced Vimeo API to generate the EOL XML.
There is a vimeo group called: Encyclopedia of Life Videos 
https://vimeo.com/groups/encyclopediaoflife

First step is to get the user IDs of all users from the group called 'encyclopediaoflife'.
Second step is to then access/get each user's list of videos using their ID.

There is also an instruction here outlining the steps on how to setup your video so it can be shown in eol.org
https://vimeo.com/groups/encyclopediaoflife/forum/topic:237888
*/
define("VIMEO_PLAYER_URL", "http://vimeo.com/moogaloop.swf?clip_id=");
define("CONSUMER_KEY", "8498d03ee2e3276f878fbbeb2354a1552bfea767");
define("CONSUMER_SECRET", "579812c7f9e9cef30ab1bf088c3d3b92073e115c");

class VimeoAPI
{
    public static function get_all_taxa($user_ids = false)
    {
        $vimeo = new \phpVimeo(CONSUMER_KEY, CONSUMER_SECRET);
        $all_taxa = array();
        $used_collection_ids = array();
        if(!$user_ids) $user_ids = self::get_list_of_user_ids($vimeo);
        $count_of_users = count($user_ids);
        $i = 0;
        foreach($user_ids as $user_id) {
            $i++;
            $page = 1;
            $count_of_videos = 0;
            while($page == 1 || $count_of_videos == 50) { //if $count_of_videos < 50 it means that this current page is the last page; default per_page = 50
                sleep(1);
                if($return = self::vimeo_call_with_retry($vimeo, 'vimeo.videos.getUploaded', array('user_id' => $user_id, 'page' => $page, "full_response" => true)))
                {
                    $count_of_videos = count($return->videos->video);
                    $j = 0;
                    foreach($return->videos->video as $video) {
                        $j++;
                        echo("\nUser $i of $count_of_users (UserID: $user_id); Video $j of $count_of_videos on page $page (VideoID: $video->id)");
                        $arr = self::get_vimeo_taxa($video, $used_collection_ids);
                        $page_taxa              = $arr[0];
                        $used_collection_ids    = $arr[1];
                        if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                    }
                    $page++;
                }
            }
        }
        return $all_taxa;
    }
    public static function vimeo_call_with_retry($vimeo, $command, $param)
    {
        $no_of_trials = 2;
        $trials = 1;
        while($trials <= $no_of_trials) {
            // if($obj = $vimeo->call($command, $param)) return $obj; => old version, without caching
            if($obj = self::lookup_with_cache_vimeo_call($vimeo, $command, $param)) return $obj;
            else {
                echo("\n Fail. Will try again in 30 seconds.");
                sleep(30);
                $trials++;
            }
        }
        echo("\nFailed after $no_of_trials tries.");
        return false;
    }
    private static function lookup_with_cache_vimeo_call($vimeo, $command, $param, $options = array())
    {
        // default expire time is 30 days
        if(!isset($options['expire_seconds'])) $options['expire_seconds'] = 2592000; //debug orig value = 2592000
        if(!isset($options['timeout']))        $options['timeout'] = 240;
        if(!isset($options['cache_path'])) $options['cache_path'] = DOC_ROOT . "tmp/cache/";
        // if(!isset($options['cache_path'])) $options['cache_path'] = "/Volumes/Eli black/eol_cache/";    //debug - only during development

        $url = $command . implode("_", $param);
        $md5 = md5($url);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        
        $options['cache_path'] .= "vimeo/";
        if(!file_exists($options['cache_path'])) mkdir($options['cache_path']);
        
        if(!file_exists($options['cache_path'] . $cache1)) mkdir($options['cache_path'] . $cache1);
        if(!file_exists($options['cache_path'] . "$cache1/$cache2")) mkdir($options['cache_path'] . "$cache1/$cache2");
        $cache_path = $options['cache_path'] . "$cache1/$cache2/$md5.cache";
        if(file_exists($cache_path)) {
            $file_contents = file_get_contents($cache_path);
            if(!Functions::is_utf8($file_contents)) $file_contents = utf8_encode($file_contents);
            $obj = json_decode($file_contents);
            
            if(($file_contents) || (strval($file_contents) == "0")) {
                $file_age_in_seconds = time() - filemtime($cache_path);
                if($file_age_in_seconds < $options['expire_seconds']) return $obj;
                if($options['expire_seconds'] === false) return $obj;
            }
            @unlink($cache_path);
        }

        if($obj = $vimeo->call($command, $param)) {
            $file_contents = json_encode($obj);
            if($FILE = Functions::file_open($cache_path, 'w+')) { // normal
                fwrite($FILE, $file_contents);
                fclose($FILE);
            }
            else { // can happen when cache_path is from external drive with corrupt dir/file
                if(!($h = Functions::file_open(DOC_ROOT . "/public/tmp/cant_delete.txt", 'a'))) return;
                fwrite($h, $cache_path . "\n");
                fclose($h);
            }
            return $obj;
        }
        return false;
    }
    private static function get_list_of_user_ids($vimeo)
    {
        //get the members of the group
        $user_ids = array();
        $page = 1;
        while(!$user_ids || count($user_ids) % 20 == 0) //if count($user_ids) is not a multiple of 20 it means that this current page is the last page; default per_page = 50
        {
            echo("\npage: $page");
            $return = self::vimeo_call_with_retry($vimeo, 'vimeo.groups.getMembers', array('group_id' => "encyclopediaoflife", 'page' => $page, 'per_page' => 20));
            echo(" - " . count($return->members->member) . " members");
            $loop = array();
            if(count($return->members->member) == 1) $loop[] = $return->members->member;
            else                                     $loop = $return->members->member;
            foreach($loop as $member) $user_ids[(string) $member->id] = 1;
            $page++;
        }
        //get the moderators of the group
        $return = self::vimeo_call_with_retry($vimeo, 'vimeo.groups.getModerators', array('group_id' => "encyclopediaoflife", 'page' => 1));
        foreach($return->moderators->moderator as $moderator) $user_ids[(string) $moderator->id] = 1;

        /*
        $user_ids = array();
        $user_ids['user7837321'] = 1;
        $user_ids['user5814509'] = 1; //katja
        $user_ids['user5352360'] = 1; //eli
        $user_ids['5352360']     = 1; //eli
        $user_ids['user1632860'] = 1; //peter kuttner
        */
        unset($user_ids["1632860"]); //Tamborine's videos are moved to the main Tamborine EOL account (DATA-1592)
        return array_keys($user_ids);
    }
    public static function get_vimeo_taxa($rec, $used_collection_ids)
    {
        $response = self::parse_xml($rec); //this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec) {
            if(@$used_collection_ids[$rec["taxon_id"]]) continue;
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["taxon_id"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }
    private static function parse_xml($rec)
    {
        $arr_data = array();
        $description = Functions::import_decode($rec->description);
        $description = str_ireplace("<br />", "", $description);

        $license = "";
        $arr_sciname = array();
        if(preg_match_all("/\[(.*?)\]/ims", $description, $matches)) {//gets everything between brackets []
            $smallest_taxa = self::get_smallest_rank($matches[1]);
            $smallest_rank = @$smallest_taxa['rank'];
            $sciname       = $smallest_taxa['name'];
            //smallest rank sciname: [$smallest_rank][$sciname]
            $multiple_taxa_YN = self::is_multiple_taxa_video($matches[1]);
            if(!$multiple_taxa_YN) $arr_sciname = self::initialize($sciname);
            foreach($matches[1] as $tag) {
                $tag=trim($tag);
                if($multiple_taxa_YN) {
                    if(is_numeric(stripos($tag,$smallest_rank))) {
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
        if(isset($rec->tags)) {
            foreach($rec->tags->tag as $tag) {
                $tag = trim($tag->{"_content"});
                if($tag == "eol") $with_eol_tag = true;
                elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr)) $license = strtolower(trim($arr[1])); //users might put the license in a tag
            }
        }

        /* Not a pre-requisite anymore to have an 'eol' tag
        if(!$with_eol_tag) return array();
        */

        if($license) $license = self::get_cc_license($license); //license from Vimeo tag or description section
        else {
            if($license = $rec->license) $license = self::get_cc_license($license);
            else {
                /* working but commented since it is too heavy with all those extra page loads, the total no. didn't actually change so this step can be excluded
                $license = self::get_license_from_page($rec->urls->url{0}->{"_content"}); //license from Vimeo license settings - scraped from the video page
                */
                $license = false;
            }
        }

        //has to have a valid license
        if(!$license) {
            echo("\ninvalid license: " . $rec->urls->url{0}->{"_content"});
            return array();
        }

        foreach($arr_sciname as $sciname => $temp) {
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
            $source      = $rec->urls->url{0}->{"_content"};
            $mediaURL    = VIMEO_PLAYER_URL . $rec->id;
            $thumbnailURL = $rec->thumbnails->thumbnail{2}->{"_content"}; //$rec->thumbnail_large;
            $agent = array();
            if($rec->owner->display_name) $user_name = $rec->owner->display_name;
            elseif($rec->owner->realname) $user_name = $rec->owner->realname;
            if($user_name) $agent = array(0 => array("role" => "creator" , "homepage" => $rec->owner->profileurl , 'fullName' => $user_name));
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects);
            //end data objects //----------------------------------------------------------------------------------------

            $taxon_id   = str_ireplace(" ", "_", $sciname) . "_" . $rec->id;
            if($val = self::adjust_sciname($arr_sciname, $sciname)) $new_sciname = $val;
            else                                                    $new_sciname = $sciname;
            $arr_data[]=array(  "identifier"   => "",
                                "source"       => "",
                                "kingdom"      => $arr_sciname[$sciname]['kingdom'],
                                "phylum"       => $arr_sciname[$sciname]['phylum'],
                                "class"        => $arr_sciname[$sciname]['class'],
                                "order"        => $arr_sciname[$sciname]['order'],
                                "family"       => $arr_sciname[$sciname]['family'],
                                "genus"        => $arr_sciname[$sciname]['genus'],
                                "sciname"      => $new_sciname,
                                "taxon_id"     => $taxon_id,
                                "commonNames"  => @$arr_sciname[$sciname]['commonNames'],
                                "arr_objects"  => $arr_objects
                             );
        }
        return $arr_data;
    }
    public static function adjust_sciname($arr_sciname, $sciname)
    {
        /* if there is genus e.g. "Testudo", and species e.g. "T. marginata", then it will return "Testudo marginata" */
        if(@$arr_sciname[$sciname]['genus']) {
            $string = substr($sciname,0,2);
            if($string == substr($arr_sciname[$sciname]['genus'],0,1) . ".") {
                $parts = explode(" ", $sciname);
                if(count($parts) == 2) return trim($arr_sciname[$sciname]['genus'] . " " . $parts[1]);
            }
        }
        return false;
    }
    public static function initialize($sciname, $arr_sciname=NULL)
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
    public static function is_multiple_taxa_video($arr)
    {
        $taxa = array();
        foreach($arr as $tag) {
            if(preg_match("/^taxonomy:(.*)\=/i", $tag, $arr)) {
                $rank = trim($arr[1]);
                if(in_array($rank,$taxa)) return 1;
                $taxa[] = $rank;
            }
        }
        return 0;
    }
    public static function get_smallest_rank($match, $rec) //$rec is just for debug
    {   /*
        [0] => taxonomy:order=Lepidoptera&nbsp;[taxonomy:family=Lymantriidae
        */
        $rank_id = array("trinomial" => 1, "binomial" => 2, "species" => 3, "genus" => 4, "family" => 5, "order" => 6, "class" => 7, "phylum" => 8, "kingdom" => 9);
        $smallest_rank_id = 10;
        $smallest_rank = "";
        foreach($match as $tag) {
            if(preg_match("/^taxonomy:(.*)\=/i", $tag, $arr)) {
                $rank = strtolower(trim($arr[1]));
                if(in_array($rank, array_keys($rank_id))) {
                    if($rank_id[$rank] < $smallest_rank_id) {
                        $smallest_rank_id = $rank_id[$rank];
                        $smallest_rank = $rank;
                    }
                }
            }
        }
        foreach($match as $tag) {
            if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
        }
        if(!isset($sciname)) {
            echo("\nThis needs checking...".$rec['uri']);
            print_r($match);
            $sciname = '';
            /* for debugging
            if(stripos($match[0], 'Higa') !== false) exit("\n - investigate - \n");
            */
        }
        return array("rank" => $smallest_rank, "name" => $sciname);
    }
    public static function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects)
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

        if($rec["sciname"]!=@$rec["family"])$taxon["family"] = ucfirst(trim(@$rec["family"]));
        if($rec["sciname"]!=@$rec["genus"])$taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        if($rec["sciname"]!=@$rec["order"])$taxon["order"] = ucfirst(trim(@$rec["order"]));
        if($rec["sciname"]!=@$rec["class"])$taxon["class"] = ucfirst(trim(@$rec["class"]));
        if($rec["sciname"]!=@$rec["phylum"])$taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        if($rec["sciname"]!=@$rec["kingdom"])$taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));

        foreach($rec["commonNames"] as $comname) {
            $taxon["commonNames"][] = new \SchemaCommonName(array("name" => $comname, "language" => ""));
        }

        if($rec["arr_objects"]) {
            foreach($rec["arr_objects"] as $object) {
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
        //==========================================================================================
        $agents = array();
        foreach(@$rec["agent"] as $agent) {
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";
            $agentParameters["fullName"] = $agent['fullName'];
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $data_object_parameters["agents"] = $agents;
        //==========================================================================================
        return $data_object_parameters;
    }
    public static function get_cc_license($license)
    {
        switch($license) {
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
            case 'by':
                return 'http://creativecommons.org/licenses/by/3.0/'; break;
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
        $html = Functions::lookup_with_cache($video_page_url, array('expire_seconds' => 2592000)); // 30 days until cache expires //debug orig value = 2592000
        if(preg_match("/<a href=\"http:\/\/creativecommons.org\/licenses\/(.*?)\//ims", $html, $matches)) return self::get_cc_license("cc-" . trim($matches[1]));
        return false;
    }
}
?>
