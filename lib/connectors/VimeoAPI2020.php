<?php
namespace php_active_record;
require_once DOC_ROOT.'/vendor/vimeo_api/vendor/autoload.php';
/* connector: vimeo2020.php --- DATA-1864 */
/* connector: 214 
Connector makes use of the Advanced Vimeo API to generate the EOL XML.
There is a vimeo group called: Encyclopedia of Life Videos 
https://vimeo.com/groups/encyclopediaoflife

First step is to get the user IDs of all users from the group called 'encyclopediaoflife'.
Second step is to then access/get each user's list of videos using their ID.

There WAS also an instruction here outlining the steps on how to setup your video so it can be shown in eol.org
https://vimeo.com/groups/encyclopediaoflife/forum/topic:237888
*/
define("CLIENT_ID", "8498d03ee2e3276f878fbbeb2354a1552bfea767");
define("CLIENT_SECRET", "579812c7f9e9cef30ab1bf088c3d3b92073e115c");
define("ACCESS_TOKEN", "be68020e45bf5677e69034c8c2cfc91b");

class VimeoAPI2020
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array('resource_id' => $this->resource_id, 'expire_seconds' => 60*60*24*25*2, 'download_wait_time' => 1000000, 
        'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
    }
    public function start()
    {
        require_library('connectors/VimeoAPI');
        $this->func = new VimeoAPI();
        
        // use Vimeo\Vimeo; //from API doc reference but did not use. Used below instead to work in EOL codebase.
        $client = new \Vimeo\Vimeo(CLIENT_ID, CLIENT_SECRET, ACCESS_TOKEN);
        /* normal operation
        $all_users = self::get_all_users_from_group('encyclopediaoflife', $client); //group ID = 'encyclopediaoflife'
        */
        // /* during dev only
        $all_users = Array(
                5814509 => Array(
                        "name" => "Katja S.",
                        "link" => "https://vimeo.com/user5814509",
                        "videos" => "/users/5814509/videos"));
        // */
        self::main_prog($all_users, $client);
        exit("\n-end for now-\n");
    }
    private function main_prog($all_users, $client)
    {   /*Array(
            [5814509] => Array(
                    [name] => Katja S.
                    [link] => https://vimeo.com/user5814509
                    [videos] => /users/5814509/videos
                )
        )*/
        foreach($all_users as $user_id => $rec) {
            self::process_user($user_id, $rec, $client);
        }
    }
    private function process_user($user_id, $rec, $client) //process all videos of a user
    {
        /*
        $videos = $client->request($rec['videos'], array(), 'GET'); // print_r($videos);
        foreach($videos as $rec) {
            self::process_video($rec);
        }
        */
        $uri = $rec['videos'];
        while(true) {
            $videos = $client->request($uri, array(), 'GET');
            echo "\n".count($videos['body']['data'])."\n";
            // print_r($videos); exit("\n100\n");
            // /* loop process
            foreach($videos['body']['data'] as $rec) {
                $eli = self::process_video($rec);
                // exit("\naaa\n");
                // if($eli) exit("\nbbb\n");
            }
            // */
            if($next = $videos['body']['paging']['next']) $uri = $next;
            else break;
        }//end while loop
    }
    
    private function process_video($rec)
    {
        // print_r($rec); exit("\nelix\n");
        /*Array(
            [uri] => /videos/48269442
            [name] => Argyrodes elevatus
            [description] => Argyrodes elevatus on the web of the yellow garden spider, Argiope aurantia. Heritage Island, Anacostia River, Washington, DC, USA. 15 August 2012.
        You can see Argyrodes moving around in the web of its host and in one of the clips it settles down on the prey while Argiope is feeding on the other side.
        It was a rainy day and not many things were flying between showers, so I thought I'd do a little video of this Argiope sucking on its food.  Initially, I didn't even see the little kleptoparasite lurking in the web.  When I finally saw it, I knew immediately what it was because I had seen it in a David Attenborough movie.  It was great fun to finally see one in real life.  From now on, I'll have to stop and closely inspect every one of these webs.
            [type] => video
            [link] => https://vimeo.com/48269442
            ...
        */
        
        $arr_data = array();
        $description = Functions::import_decode($rec['description']);
        $description = str_ireplace("<br />", "", $description);

        $license = "";
        $arr_sciname = array();
        if(preg_match_all("/\[(.*?)\]/ims", $description, $matches)) {//gets everything between brackets []
            $smallest_taxa = $this->func->get_smallest_rank($matches[1]);
            $smallest_rank = $smallest_taxa['rank'];
            $sciname       = $smallest_taxa['name'];
            //smallest rank sciname: [$smallest_rank][$sciname]
            $multiple_taxa_YN = $this->func->is_multiple_taxa_video($matches[1]);
            if(!$multiple_taxa_YN) $arr_sciname = $this->func->initialize($sciname);
            foreach($matches[1] as $tag) {
                $tag=trim($tag);
                if($multiple_taxa_YN) {
                    if(is_numeric(stripos($tag,$smallest_rank))) {
                        if(preg_match("/^taxonomy:" . $smallest_rank . "=(.*)$/i", $tag, $arr)) $sciname = ucfirst(trim($arr[1]));
                        $arr_sciname = $this->func->initialize($sciname,$arr_sciname);
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

        /* Not a pre-requisite anymore to have an 'eol' tag
        $with_eol_tag = false;
        if(isset($rec->tags)) {
            foreach($rec->tags->tag as $tag) {
                $tag = trim($tag->{"_content"});
                if($tag == "eol") $with_eol_tag = true;
                elseif(preg_match("/^dc:license=(.*)$/i", $tag, $arr)) $license = strtolower(trim($arr[1])); //users might put the license in a tag
            }
        }
        if(!$with_eol_tag) return array();
        */

        if($license) $license = $this->func->get_cc_license($license); //license from Vimeo tag or description section
        else {
            if($license = $rec['license']) $license = $this->func->get_cc_license($license);
            else {
                /* working but commented since it is too heavy with all those extra page loads, the total no. didn't actually change so this step can be excluded
                $license = self::get_license_from_page($rec->urls->url{0}->{"_content"}); //license from Vimeo license settings - scraped from the video page
                */
                $license = false;
            }
        }

        //has to have a valid license
        if(!$license) {
            echo("\ninvalid license:\n[$license]\n");
            return array();
        }

        foreach($arr_sciname as $sciname => $temp) {
            if(!$sciname && @$arr_sciname[$sciname]['trinomial']) $sciname = @$arr_sciname[$sciname]['trinomial'];
            if(!$sciname && @$arr_sciname[$sciname]['genus'] && @$arr_sciname[$sciname]['species'] && !preg_match("/ /", @$arr_sciname[$sciname]['genus']) && !preg_match("/ /", @$arr_sciname[$sciname]['species'])) $sciname = @$arr_sciname[$sciname]['genus']." ".@$arr_sciname[$sciname]['species'];                        
            if(!$sciname && !@$arr_sciname[$sciname]['genus'] && !@$arr_sciname[$sciname]['family'] && !@$arr_sciname[$sciname]['order'] && !@$arr_sciname[$sciname]['class'] && !@$arr_sciname[$sciname]['phylum'] && !@$arr_sciname[$sciname]['kingdom']) return array();

            //start data objects //----------------------------------------------------------------------------------------
            $arr_objects = array();
            $identifier  = pathinfo($rec['uri'], PATHINFO_FILENAME); //e.g. 48269442
            $dataType    = "http://purl.org/dc/dcmitype/MovingImage";
            $mimeType    = "video/mp4";
            if($val = trim($rec['name'])) $title = $val;
            else                          $title = "Vimeo video";
            $source      = $rec['link']; //$rec->urls->url{0}->{"_content"};
            $mediaURL    = self::get_mp4_url($rec['embed']['html']);
            $thumbnailURL = @$rec['pictures']['sizes'][0]['link']; //$rec->thumbnails->thumbnail{2}->{"_content"}; //$rec->thumbnail_large;
            $agent = array();
            if($val = $rec['user']['name']) $user_name = $val;
            if($user_name) $agent = array(0 => array("role" => "creator", "homepage" => $rec['user']['link'], "logoURL" => $rec['user']['pictures']['sizes'][1]['link'],"fullName" => $user_name));
            $arr_objects = $this->func->add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $thumbnailURL, $arr_objects);
            //end data objects //----------------------------------------------------------------------------------------

            $taxon_id   = str_ireplace(" ", "_", $sciname);
            if($val = $this->func->adjust_sciname($arr_sciname, $sciname)) $new_sciname = $val;
            else                                                           $new_sciname = $sciname;
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
        print_r($arr_data);
        return $arr_data;
    }
    private function get_mp4_url($html)
    {
        /*
        src="https://player.vimeo.com/video/48269442?badge=...
        */
        
        if(preg_match("/src=\"(.*?)\?/ims", $html, $arr)) {
            $url = $arr[1];
            
            $html = Functions::lookup_with_cache($url, $this->download_options);
            // "mime":"video/mp4","fps":29,"url":"https://vod-progressive.akamaized.net/exp=1602601456~acl=%2A%2F38079480.mp4%2A~hmac=92351066b44bf9ac9dffafa207e1bc60f68f42ddb7a283938ae650a3bde2c8e8/vimeo-prod-skyfire-std-us/01/3816/0/19082391/38079480.mp4","cdn"
            if(preg_match("/\"mime\":\"video\/mp4\"(.*?)\.mp4\"/ims", $html, $arr)) {
                $str = $arr[1];
                echo "\n$str\n";
                // ,"fps":29,"url":"https://vod-progressive.akamaized.net/exp=1602601908~acl=%2A%2F38079480.mp4%2A~hmac=1853127a5ec9959d6be10883146d0a544bf19d7e1834d2168dd239bb54900050/vimeo-prod-skyfire-std-us/01/3816/0/19082391/38079480
                $str .= '.mp4 xxx';
                if(preg_match("/https\:\/\/(.*?) xxx/ims", $str, $arr)) {
                    $str = $arr[1];
                    echo "\n$str\n";
                }
            }
            else exit("\nInvestigate: no mp4!\n");
            
            
        }
        
        /* works on parsing out the media URL, an mp4 for that matter!
        $url = 'https://player.vimeo.com/video/19082391';
        $url = 'https://player.vimeo.com/video/19083211';
        */
        
    }
    
    
    private function get_all_users_from_group($group_id, $client)
    {
        /* normal operation
        $arr = $client->request('/groups/encyclopediaoflife', array(), 'GET');
        $users_uri = $arr['body']['metadata']['connections']['users']['uri']; //users_uri -> "/groups/77006/users"
        */
        // echo "\n[$users_uri]\n"; print_r($arr);
        
        /* normal operation
        $all_users = self::get_all_users($users_uri, $client);
        */
        // print_r($all_users); exit;
        /*Array(
            [113877002] => Array(
                    [name] => ~{little_kitty_baby_}~
                    [link] => https://vimeo.com/muffen
                    [videos] => /users/113877002/videos
                )
            [83097635] => Array(
                    [name] => Lili Bárány
                    [link] => https://vimeo.com/user83097635
                    [videos] => /users/83097635/videos
                )
        )
        */
        
        // /* during dev only:
        $all_users = array(5814509 => Array(
                "name" => "Katja S.",
                "link" => "https://vimeo.com/user5814509",
                "videos" => "/users/5814509/videos"
            ));
        // */
        // print_r($all_users); exit;
        return $all_users;
    }
    private function get_all_users($uri, $client)
    {
        while(true) {
            $arr = $client->request($uri, array(), 'GET');
            echo "\n".count($arr['body']['data'])."\n";
            // print_r($arr); exit;
            // /* loop process
            foreach($arr['body']['data'] as $rec) { //normally loops 25 times
                // $rec
                $user_id = pathinfo($rec['uri'], PATHINFO_FILENAME); //e.g. 113877002
                $final[$user_id]['name'] = $rec['name'];
                $final[$user_id]['link'] = $rec['link'];
                $final[$user_id]['videos'] = $rec['metadata']['connections']['videos']['uri']; //e.g. /users/113877002/videos
                // print_r($final); exit;
                /*Array(
                    [113877002] => Array(
                            [name] => ~{little_kitty_baby_}~
                            [link] => https://vimeo.com/muffen
                            [videos] => /users/113877002/videos
                        )
                )*/
            }
            // */
            if($next = $arr['body']['paging']['next']) $uri = $next;
            else break;
            break; //debug only
        }//end while loop
        return $final;
    }
    //################################################################################# Below this line is from old connector.
}
?>