<?php
namespace php_active_record;
require_once DOC_ROOT.'/vendor/vimeo_api/vendor/autoload.php';
/* connector: vimeo2020.php --- DATA-1864
resource_id: 214 

The Vimeo Advanced API (we are using before) has been deprecated.
We are now using the new (working) version:
new API: https://developer.vimeo.com/api
new library : https://github.com/vimeo/vimeo.php

There is a vimeo group called: Encyclopedia of Life Videos 
https://vimeo.com/groups/encyclopediaoflife

First step is to get the user IDs of all users from the group called 'encyclopediaoflife'.
Second step is to then access/get each user's list of videos using their ID.

The old Vimeo Forum was now removed.
There WAS an instruction here outlining the steps on how to setup your video so it can be shown in eol.org
https://vimeo.com/groups/encyclopediaoflife/forum/topic:237888

This is now the latest instruction page for the latest Vimeo:
https://eol-jira.bibalex.org/browse/DATA-1864?focusedCommentId=65322&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65322
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

        $this->download_options = array('resource_id' => $this->resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 
        'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        
        if(Functions::is_production()) {
            $this->vimeo_mp4['download_path'] = '/extra/other_files/Vimeo/mp4/';
            $this->vimeo_mp4['web_access'] = 'https://editors.eol.org/other_files/Vimeo/mp4/';
        }
        else {
            $this->vimeo_mp4['download_path'] = '/Volumes/AKiTiO4/other_files/Vimeo/mp4/';
            $this->vimeo_mp4['web_access'] = 'http://localhost/other_files/Vimeo/mp4/';
        }
    }
    public function start()
    {
        require_library('connectors/VimeoAPI');
        $this->func = new VimeoAPI();
        
        // use Vimeo\Vimeo; //from API doc reference but did not use. Used below instead to work in EOL codebase.
        $client = new \Vimeo\Vimeo(CLIENT_ID, CLIENT_SECRET, ACCESS_TOKEN);
        
        // /* normal operation
        $all_users = self::get_all_users_from_group('encyclopediaoflife', $client); //group ID = 'encyclopediaoflife'
        // print_r($all_users);
        unset($all_users["1632860"]); //Peter Kuttner -> Tamborine's videos are moved to the main Tamborine EOL account (DATA-1592)
        // */
        
        /* during dev only
        $all_users = Array(
                5814509 => Array(
                        "name" => "Katja S.",
                        "link" => "https://vimeo.com/user5814509",
                        "videos" => "/users/5814509/videos"));
        $all_users = Array(
                5352360 => Array(
                        "name" => "Eli A.",
                        "link" => "https://vimeo.com/user5352360",
                        "videos" => "/users/5352360/videos"));
        */
        self::main_prog($all_users, $client);
        $this->archive_builder->finalize(true);
        print_r($this->debug);
        // exit("\n-end for now-\n");
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
        $uri = $rec['videos'];
        while(true) { //loop videos per user; per 25 batch
            $videos = $client->request($uri, array(), 'GET');
            echo "\nvideos batch for user [$user_id]: ".count($videos['body']['data'])."\n";
            // print_r($videos); exit("\n100\n");
            // /* loop process
            foreach($videos['body']['data'] as $rec) { //print_r($rec); exit("\nelix 200\n");
                $arr_data = self::process_video($rec); // print_r($arr_data);
                if($arr_data) self::write_DwCA($arr_data);
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
            $smallest_taxa = $this->func->get_smallest_rank($matches[1], $rec);
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
            if($license = $rec['license']) {
                $this->debug['raw license'][$license] = '';
                $license = $this->func->get_cc_license($license);
            }
            else {
                /* working but commented since it is too heavy with all those extra page loads, the total no. didn't actually change so this step can be excluded
                $license = self::get_license_from_page($rec->urls->url{0}->{"_content"}); //license from Vimeo license settings - scraped from the video page
                */
                $license = false;
            }
        }
        $this->debug['licenses'][$license] = '';

        //has to have a valid license
        if(!$license) {
            // echo("\ninvalid license: [$license]\n");
            return array();
        }

        foreach($arr_sciname as $sciname => $temp) {
            if(!$sciname && @$arr_sciname[$sciname]['trinomial']) $sciname = @$arr_sciname[$sciname]['trinomial'];
            if(!$sciname && @$arr_sciname[$sciname]['genus'] && @$arr_sciname[$sciname]['species'] && !preg_match("/ /", @$arr_sciname[$sciname]['genus']) && !preg_match("/ /", @$arr_sciname[$sciname]['species'])) $sciname = @$arr_sciname[$sciname]['genus']." ".@$arr_sciname[$sciname]['species'];                        
            if(!$sciname && !@$arr_sciname[$sciname]['genus'] && !@$arr_sciname[$sciname]['family'] && !@$arr_sciname[$sciname]['order'] && !@$arr_sciname[$sciname]['class'] && !@$arr_sciname[$sciname]['phylum'] && !@$arr_sciname[$sciname]['kingdom']) return array();

            //start data objects //----------------------------------------------------------------------------------------
            $arr_objects = array();
            $v = array(); //as values
            $v['identifier']  = pathinfo($rec['uri'], PATHINFO_FILENAME); //e.g. 48269442
            $v['dataType']    = "http://purl.org/dc/dcmitype/MovingImage";
            $v['mimeType']    = "video/mp4";
            if($val = trim($rec['name'])) $v['title'] = $val;
            else                          $v['title'] = "Vimeo video";
            $v['furtherInformationURL'] = $rec['link']; //$rec->urls->url{0}->{"_content"};
            $v['mediaURL']    = self::get_mp4_url($rec, $v['identifier']);
            if(!$v['mediaURL']) continue;
            $v['thumbnailURL'] = @$rec['pictures']['sizes'][0]['link']; //$rec->thumbnails->thumbnail{2}->{"_content"}; //$rec->thumbnail_large;
            $v['CreateDate']     = $rec['created_time'];
            $v['modified']       = $rec['modified_time'];

            $v['license']       = $license;
            $v['description']   = $rec['description'];
            $v['Owner'] = $rec['user']['name'];
            
            self::write_agent($rec, $v['identifier']);
            $v['agentID'] = $this->object_agent_ids[$v['identifier']];
            
            $arr_objects[] = $v;
            //end data objects //----------------------------------------------------------------------------------------

            if($val = $this->func->adjust_sciname($arr_sciname, $sciname)) $new_sciname = $val;
            else                                                           $new_sciname = $sciname;
            
            $kingdom = ($arr_sciname[$sciname]['kingdom'] != $new_sciname) ? ($arr_sciname[$sciname]['kingdom']) : "";
            $phylum = ($arr_sciname[$sciname]['phylum'] != $new_sciname) ? ($arr_sciname[$sciname]['phylum']) : "";
            $class = ($arr_sciname[$sciname]['class'] != $new_sciname) ? ($arr_sciname[$sciname]['class']) : "";
            $order = ($arr_sciname[$sciname]['order'] != $new_sciname) ? ($arr_sciname[$sciname]['order']) : "";
            $family = ($arr_sciname[$sciname]['family'] != $new_sciname) ? ($arr_sciname[$sciname]['family']) : "";
            $genus = ($arr_sciname[$sciname]['genus'] != $new_sciname) ? ($arr_sciname[$sciname]['genus']) : "";
            
            $arr_data[]=array(  //"identifier"   => "",
                                // "source"       => "",
                                "kingdom"      => $kingdom,
                                "phylum"       => $phylum,
                                "class"        => $class,
                                "order"        => $order,
                                "family"       => $family,
                                "genus"        => $genus,
                                "sciname"      => $new_sciname,
                                "taxon_id"     => md5($new_sciname),
                                "commonNames"  => @$arr_sciname[$sciname]['commonNames'],
                                "arr_objects"  => $arr_objects
                             );
        }
        return $arr_data;
    }
    private function write_agent($rec, $do_id)
    {
        $agents = array();
        if($fullName = $rec['user']['name']) {
            $agents[] = array("role"     => "creator", 
                              "homepage" => $rec['user']['link'], 
                              "logoURL"  => $rec['user']['pictures']['sizes'][1]['link'], 
                              "fullName" => $fullName);
        }
        
        $agent_ids = array();
        foreach($agents as $a) {
            if(!$a['fullName']) continue;
            $r = new \eol_schema\Agent();
            $r->term_name       = $a['fullName'];
            $r->agentRole       = $a['role'];
            $r->term_homepage   = $a['homepage'];
            $r->term_logo       = $a['logoURL'];
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier])) {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        $this->object_agent_ids[$do_id] = $agent_ids;
    }
    private function get_mp4_path($video_id)
    {
        $cache_path = $this->vimeo_mp4['download_path'];
        $md5 = $video_id; //md5($video_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($cache_path . $cache1)) mkdir($cache_path . $cache1);
        if(!file_exists($cache_path . "$cache1/$cache2")) mkdir($cache_path . "$cache1/$cache2");
        $cache_path = $cache_path . "$cache1/$cache2/$video_id.mp4";
        return array('video_path' => $cache_path, 'filename' => "$cache1/$cache2/$video_id.mp4");
    }
    private function get_mp4_url($rec, $video_id) //works on parsing out the mp4 media URL
    {
        $ret = self::get_mp4_path($video_id);
        $video_path = $ret['video_path'];
        $filename = $ret['filename'];
        if(file_exists($video_path)) return $this->vimeo_mp4['web_access'].$filename;
        else {
            if($mp4_media_url = self::get_mp4_media_url($rec['embed']['html'], $rec)) {
                $cmd = "wget -q --output-document $video_path $mp4_media_url";
                sleep(3);
                shell_exec($cmd);
                // wget --output-document example.html https://www.electrictoolbox.com/wget-save-different-filename/
                if(file_exists($video_path)) return $this->vimeo_mp4['web_access'].$filename;
                else exit("\nERROR: wget didn't work!\n");
            }
        }
        return false;
    }
    private function get_mp4_media_url($html, $rec)
    {
        /*
        src="https://player.vimeo.com/video/48269442?badge=...
        */
        if(preg_match("/src=\"(.*?)\?/ims", $html, $arr)) {
            $url = $arr[1];
            // $url = 'https://player.vimeo.com/video/19082391';
            // $url = 'https://player.vimeo.com/video/19083211';
            $options = $this->download_options;
            $options['expire_seconds'] = 60*50; //50 mins
            $html = Functions::lookup_with_cache($url, $options);
            // "mime":"video/mp4","fps":29,"url":"https://vod-progressive.akamaized.net/exp=1602601456~acl=%2A%2F38079480.mp4%2A~hmac=92351066b44bf9ac9dffafa207e1bc60f68f42ddb7a283938ae650a3bde2c8e8/vimeo-prod-skyfire-std-us/01/3816/0/19082391/38079480.mp4","cdn"
            if(preg_match("/\"mime\":\"video\/mp4\"(.*?)\.mp4\"/ims", $html, $arr)) {
                $str = $arr[1];
                // echo "\n$str\n";
                // ,"fps":29,"url":"https://vod-progressive.akamaized.net/exp=1602601908~acl=%2A%2F38079480.mp4%2A~hmac=1853127a5ec9959d6be10883146d0a544bf19d7e1834d2168dd239bb54900050/vimeo-prod-skyfire-std-us/01/3816/0/19082391/38079480
                $str .= '.mp4 xxx';
                if(preg_match("/https\:\/\/(.*?) xxx/ims", $str, $arr)) return 'https://'.$arr[1];
            }
            else {
                if(!in_array($rec['uri'], array('/videos/49579196', '/videos/49576140'))) {
                    // print_r($rec);
                    // exit("\nInvestigate: no mp4!\n");
                    $this->debug['missing videos'][$rec['uri']] = '';
                }
            }
        }
    }
    private function write_DwCA($reks)
    {
        // print_r($reks); exit("\nelix 100\n");
        /*Array(
            [0] => Array(
                    [identifier] => 
                    [source] => 
                    [kingdom] => 
                    [phylum] => 
                    [class] => 
                    [order] => 
                    [family] => 
                    [genus] => 
                    [sciname] => Odontoloxozus longicornis
                    [taxon_id] => Odontoloxozus_longicornis
                    [commonNames] => Array()
                    [arr_objects] => Array(
                            [0] => Array(
                                    [identifier] => 31027218
                                    [dataType] => http://purl.org/dc/dcmitype/MovingImage
                                    [mimeType] => video/mp4
                                    [title] => Cactus flies sexual competition
                                    [source] => https://vimeo.com/31027218
                                    [description] => On the cracking surface of a rotting opuntia branch, a large male cactus fly (Odontoloxozus longicornis) chases away a small male that has just dismounted a female. The large male guards the female by standing over her while she is feeding in crevices on the cactus surface. The large male then goes on to feed, while the female begins to oviposit into a crevice. Undetected by the large male, the small male approaches the female again and mates with her. When other males approach the mating pair, the large male defends the female, who has started ovipositing again while the small male crouches behind her, apparently hiding from the large male. Even as he is standing above the pair, the large male does not seem to notice the small male who swiftly slips away. The large male establishes genital contact, repeatedly stroking the female's ovipositor with his aedeagus, then dismounts. Tucson, Pima County, Arizona, USA.  16 November 2008.&nbsp;&nbsp;
                                    [mediaURL] => vod-progressive.akamaized.net/exp=1602735520~acl=%2A%2F70034528.mp4%2A~hmac=dc6e3207d93ae4d63f0cafc094d8e435d48ccc5f7c10d1dedf5f4edaf036c4bf/vimeo-prod-skyfire-std-us/01/1205/1/31027218/70034528.mp4
                                    [agentID] => 
                                    [license] => http://creativecommons.org/licenses/by/3.0/
                                    [thumbnailURL] => https://i.vimeocdn.com/video/208771712_100x75.jpg?r=pad
                                )
                        )
                )
        )*/
        foreach($reks as $rek) {
            self::write_taxon($rek);
            if($val = $rek['commonNames']) self::write_comnames($val, $rek['taxon_id']);
            if($val = $rek['arr_objects']) self::write_objects($val, $rek['taxon_id']);
        }
    }
    private function write_comnames($comnames, $taxon_id)
    {
        foreach($comnames as $name) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $taxon_id;
            $v->vernacularName  = $name;
            $v->language        = 'en';
            $id = md5("$v->taxonID|$v->vernacularName|$v->language");
            if(!isset($this->vernaculars[$id])) {
                $this->archive_builder->write_object_to_file($v);
                $this->vernaculars[$id] = '';
            }
        }
    }
    private function write_objects($objects, $taxonID) //normally just 1 object pass here
    {
        foreach($objects as $o) {
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $taxonID;
            $mr->identifier     = $o['identifier'];
            $mr->type           = $o['dataType'];
            $mr->language       = 'en';
            $mr->format         = $o['mimeType'];
            $mr->furtherInformationURL = $o['furtherInformationURL'];
            $mr->accessURI      = $o['mediaURL'];
            $mr->thumbnailURL   = $o['thumbnailURL'];
            $mr->Owner          = $o['Owner']; //$o['dc_rightsHolder'];
            // $mr->rights         = ''; //$o['dc_rights'];
            $mr->title          = $o['title'];
            $mr->UsageTerms     = $o['license'];
            $mr->description    = utf8_encode($o['description']);

            // $mr->bibliographicCitation = ''; //$o['dcterms_bibliographicCitation'];
            // $mr->audience       = 'Everyone';
            // $mr->CVterm         = '';
            // $mr->LocationCreated = ''; //$o['location'];

            $mr->CreateDate     = $o['CreateDate'];
            $mr->modified       = $o['modified'];
            
            // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids     =     @$this->object_agent_ids[$o['identifier']])  $mr->agentID = implode("; ", $agent_ids);
            
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
    }
    private function write_taxon($rek)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rek['taxon_id'];
        $taxon->scientificName  = $rek['sciname'];
        $taxon->kingdom         = $rek['kingdom'];
        $taxon->phylum          = $rek['phylum'];
        $taxon->class           = $rek['class'];
        $taxon->order           = $rek['order'];
        $taxon->family          = $rek['family'];
        $taxon->genus           = $rek['genus'];
        if(!isset($this->taxonIDs[$taxon->taxonID])) {
            $this->taxonIDs[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function get_all_users_from_group($group_id, $client)
    {
        // /* normal operation
        $arr = $client->request('/groups/encyclopediaoflife', array(), 'GET');
        $users_uri = $arr['body']['metadata']['connections']['users']['uri']; //users_uri -> "/groups/77006/users"
        // */
        // echo "\n[$users_uri]\n"; print_r($arr);
        
        // /* normal operation
        $all_users = self::get_all_users($users_uri, $client);
        // */
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
        
        /* during dev only:
        $all_users = array(5814509 => Array(
                "name" => "Katja S.",
                "link" => "https://vimeo.com/user5814509",
                "videos" => "/users/5814509/videos"
            ));
        */
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
            // break; //debug only
        }//end while loop
        return $final;
    }
    //################################################################################# Below this line is from old connector.
}
?>