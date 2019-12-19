<?php
namespace php_active_record;
/* connector: [flickr_user_album.php, 958.php] */
class FlickrUserAlbumAPI
{
    function __construct($resource_id)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => 5184000, 'timeout' => 7200, 'download_wait_time' => 1000000); // 2 months expire_seconds
        // $this->download_options['expire_seconds'] = false;
        $this->service['photosets'] = 'https://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=' . FLICKR_API_KEY . '&format=json&nojsoncallback=1';
    }
    function convert_to_dwca($params)
    {
        require_library('FlickrAPI');
        $auth_token = NULL;
        // if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;
        $page = 1;
        $per_page = 500;
        $url = $this->service['photosets'] . '&photoset_id=' . $params['photoset_id'] . '&user_id=' . $params['flickr_user_id'] . '&per_page=' . $per_page;
        if($json = Functions::lookup_with_cache($url.'&page='.$page, $this->download_options)) {
            $json = str_replace("\\'", "'", $json);
            $obj = json_decode($json);
            $total_pages = ceil($obj->photoset->total / $per_page);
            echo "\ntotal_pages = $total_pages\n";

            for($i=1 ; $i<=$total_pages ; $i++) {
                if($json = Functions::lookup_with_cache($url.'&page='.$page, $this->download_options)) {
                    $json = str_replace("\\'", "'", $json);
                    $obj = json_decode($json);
                    
                    $k = 0;
                    $total_photos = count($obj->photoset->photo);
                    
                    foreach($obj->photoset->photo as $rec) {
                        $k++;
                        echo "\n$i of $total_pages - $k of $total_photos";
                        if(!($sciname = self::get_sciname_from_title($rec->title))) continue;

                        // if($sciname == "SONY DSC") //debug
                        // {
                        //     print_r($rec);
                        // }

                        $photo_response = FlickrAPI::photos_get_info($rec->id, $rec->secret, $auth_token, $this->download_options);
                        $photo = @$photo_response->photo;

                        if(!$photo) continue;
                        if($photo->visibility->ispublic != 1)               continue;
                        if($photo->usage->candownload != 1)                 continue;
                        if(@!$GLOBALS["flickr_licenses"][$photo->license])  continue;
                        
                        $data_objects = FlickrAPI::get_data_objects($photo, $params['flickr_user_id']);
                        foreach($data_objects as $do) self::create_archive($sciname, $do);
                    }
                }
                $page++;
                // break; //debug
            }
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function create_archive($sciname, $do)
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID                 = strtolower(str_replace(" ", "_", $sciname));
        $t->scientificName          = $sciname;
        if(!isset($this->taxon_ids[$t->taxonID])) {
            $this->taxon_ids[$t->taxonID] = '';
            $this->archive_builder->write_object_to_file($t);
        }

        $agent_ids = self::create_agents($do->agents);
        $mr = new \eol_schema\MediaResource();
        if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID        = $t->taxonID;
        $mr->identifier     = $do->identifier;
        $mr->type           = $do->dataType;
        $mr->language       = $do->language;
        $mr->format         = $do->mimeType;
        $mr->Owner          = $do->rightsHolder;
        $mr->rights         = $do->rights;
        $mr->title          = $do->title;
        $mr->UsageTerms     = $do->license;
        $mr->description    = $do->description;
        $mr->accessURI      = $do->mediaURL;
        $mr->CreateDate        = $do->created;
        $mr->modified        = $do->modified;
        $mr->LocationCreated        = $do->location;
        $mr->bibliographicCitation    = $do->bibliographicCitation;
        $mr->furtherInformationURL     = $do->source;
        // $mr->Rating = '';
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->object_ids[$mr->identifier] = '';
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function create_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $rec) {
            if(!($agent = (string) trim($rec->fullName))) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|" . $rec->role);
            $r->agentRole = $rec->role;
            $r->term_homepage = $rec->homepage;
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier])) {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function get_sciname_from_title($title)
    {
        // $title = "E110_2_Vitex_agnus-castus_Keuschbaum_2";
        // $title = "0997Brassica_oleracea_GemueseKohl_2";
        // $title = "S214_3a_Clycyrrhiza_echinata_RoemischesSuessholz_3";
        
        $arr = explode("_", $title);
        $sciname = "";
        foreach($arr as $index => $value) {
            if(ctype_alpha(substr($value,0,1)) && self::has_digit($value)) $arr[$index] = null; //e.g. E110_2_Vitex_agnus-castus_Keuschbaum_2
            if(is_numeric($value))                                            $arr[$index] = null;
        }
        $arr = array_filter($arr); //remove null arrays
        $arr = array_values($arr); //reindex key

        $title = implode("_", $arr);
        $title = preg_replace('/[0-9]+/', '', $title);
        $arr = explode("_", $title);
        
        foreach($arr as $index => $value) {
            if(strlen($value) == 1) $arr[$index] = null;
        }
        $arr = array_filter($arr); //remove null arrays
        $arr = array_values($arr); //reindex key
        
        foreach($arr as $index => $value) {
            if($index == 0) $sciname .= $value;
            else {
                if(ctype_lower(substr($value,0,1))) $sciname .= " $value";
                else break;
            }
        }
        
        //e.g. aRubus canescens
        if(ctype_lower(substr($sciname,0,1))) {
            $sciname = trim(substr($sciname,1,strlen($sciname)));
        }
        
        $sciname = trim($sciname);
        if(in_array($sciname, array("SONY DSC"))) return false; //manual adjustment e.g. https://www.flickr.com/photos/56006259@N06/19288551182/in/photostream/
        
        return $sciname;
    }
    private function has_digit($str)
    {
        $digits = "0,1,2,3,4,5,6,7,8,9";
        foreach(explode(",", $digits) as $digit) {
            if(is_numeric(strpos($str, $digit))) return true;            
        }
        return false;
    }
}
?>