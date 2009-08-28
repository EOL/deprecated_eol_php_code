<?php

class FlickrAPI
{
    public static function get_all_eol_photos($auth_token = "")
    {
        //return self::get_eol_photos(100, 1);
        
        $taxa = array();
        $per_page = 100;
        
        // Get metadata about the EOL Flickr pool
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", 1, 1, $auth_token);
        if($response)
        {
            $total = $response->photos["total"];
            
            // number of API calls to be made
            $total_pages = ceil($total / 100);
            
            $taxa = array();
            for($i=1 ; $i<=$total_pages ; $i++)
            {
                Functions::display("getting page $i");
                $page_taxa = self::get_eol_photos($per_page, $i, $auth_token);
                $taxa = array_merge($taxa, $page_taxa);
            }
        }
        
        return $taxa;
    }
    
    public static function get_eol_photos($per_page, $page, $auth_token = "")
    {
        echo "GET_PHOTOS: $page, $per_page\n";
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", $per_page, $page, $auth_token);
        
        $taxa = array();
        foreach($response->photos->photo as $photo)
        {
            $taxon = self::get_taxon_for_photo($photo["id"], $photo["secret"], $auth_token);
            if($taxon) $taxa[(string) $photo["id"]] = $taxon;
        }
        
        return $taxa;
    }
    
    public static function get_taxon_for_photo($photo_id, $secret, $auth_token = "")
    {
        echo "PHOTO_ID: $photo_id\n";
        $photo_response = self::photos_get_info($photo_id, $secret, $auth_token);
        $photo = $photo_response->photo;
        if(!$photo) Functions::debug("\n\nERROR:Photo $photo_id is not available\n\n");
        
        if($photo->visibility["ispublic"] != 1) return false;
        if($photo->usage["candownload"] != 1) return false;
        
        if(@!$GLOBALS["flickr_licenses"][(string) $photo["license"]]) return false;
        
        $taxon_parameters = array();
        $taxon_parameters["commonNames"] = array();
        foreach($photo->tags->tag as $tag)
        {
            $string = trim((string) $tag["raw"]);
            
            if(preg_match("/^taxonomy:subspecies=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["subspecies"]) return false;
                $taxon_parameters["subspecies"] = strtolower(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:trinomial=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["trinomial"]) return false;
                $taxon_parameters["trinomial"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:species=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["species"]) return false;
                $taxon_parameters["species"] = strtolower(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:binomial=(.*)$/i", $string, $arr)) 
            {
                if(@$taxon_parameters["scientificName"]) return false;
                $taxon_parameters["scientificName"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:genus=(.*)$/i", $string, $arr)) 
            {
                if(@$taxon_parameters["genus"]) return false;
                $taxon_parameters["genus"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:family=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["family"]) return false;
                $taxon_parameters["family"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:order=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["order"]) return false;
                $taxon_parameters["order"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:class=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["class"]) return false;
                $taxon_parameters["class"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:phylum=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["phylum"]) return false;
                $taxon_parameters["phylum"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:kingdom=(.*)$/i", $string, $arr))
            {
                if(@$taxon_parameters["kingdom"]) return false;
                $taxon_parameters["kingdom"] = ucfirst(trim($arr[1]));
            }elseif(preg_match("/^taxonomy:common=(.*)$/i", $string, $arr)) $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => trim($arr[1])));
        }
        
        if(@$taxon_parameters["trinomial"]) $taxon_parameters["scientificName"] = $taxon_parameters["trinomial"];
        if(@!$taxon_parameters["scientificName"] && @$taxon_parameters["genus"] && @$taxon_parameters["species"] && !preg_match("/ /", $taxon_parameters["genus"]) && !preg_match("/ /", $taxon_parameters["species"])) $taxon_parameters["scientificName"] = $taxon_parameters["genus"]." ".$taxon_parameters["species"];
        if(@!$taxon_parameters["genus"] && @preg_match("/^([^ ]+) /", $taxon_parameters["scientificName"], $arr)) $taxon_parameters["genus"] = $arr[1];
        if(@!$taxon_parameters["scientificName"] && @!$taxon_parameters["genus"] && @!$taxon_parameters["family"] && @!$taxon_parameters["order"] && @!$taxon_parameters["class"] && @!$taxon_parameters["phylum"] && @!$taxon_parameters["kingdom"]) return false;
        
        
        
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = (string) $photo["id"];
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["mimeType"] = "image/jpeg";
        $data_object_parameters["title"] = (string) $photo->title;
        $data_object_parameters["description"] = (string) $photo->description;
        $data_object_parameters["mediaURL"] = self::photo_url($photo["id"], $photo["secret"], $photo["server"], $photo["farm"]);
        $data_object_parameters["license"] = @$GLOBALS["flickr_licenses"][(string) $photo["license"]];
        if($photo->dates["taken"]) $data_object_parameters["created"] = (string) $photo->dates["taken"];
        
        foreach($photo->urls->url as $url)
        {
            if($url["type"]=="photopage") $data_object_parameters["source"] = (string) $url;
        }
        
        
        $agent_parameters = array();
        if(trim($photo->owner["realname"]) != "") $agent_parameters["fullName"] = (string) $photo->owner["realname"];
        else $agent_parameters["fullName"] = (string) $photo->owner["username"];
        $agent_parameters["homepage"] = "http://www.flickr.com/photos/".$photo->owner["nsid"];
        $agent_parameters["role"] = "photographer";
        
        $data_object_parameters["agents"] = array();
        $data_object_parameters["agents"][] = new SchemaAgent($agent_parameters);
        
        if($photo->geoperms["ispublic"] = 1)
        {
            $geo_point_parameters = array();
            $geo_point_parameters["latitude"] = (string) $photo->location["latitude"];
            $geo_point_parameters["longitude"] = (string) $photo->location["longitude"];
            $data_object_parameters["point"][] = new SchemaPoint($geo_point_parameters);
            
            $locations = array();
            if($photo->location->locality) $locations[0] = (string) $photo->location->locality;
            if($photo->location->region) $locations[1] = (string) $photo->location->region;
            if($photo->location->country) $locations[2] = (string) $photo->location->country;
            
            if($locations) $data_object_parameters["location"] = implode(", ", $locations);
        }
        
        $data_object = new SchemaDataObject($data_object_parameters);
        
        $taxon_parameters["dataObjects"] = array();
        $taxon_parameters["dataObjects"][] = $data_object;
        
        
        
        // If the media type is video, there should be a Video Player type. Add that as a second data object
        if($photo["media"] == "video")
        {
            Functions::debug("getting sizes for id: ".$photo["id"]."\n");
            $sizes = self::photos_get_sizes($photo["id"]);
            if(@$sizes)
            {
                foreach($sizes->sizes->size as $size)
                {
                    if($size["label"] == "Video Player")
                    {
                        $data_object_parameters["identifier"] .= "_video";
                        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/MovingImage";
                        $data_object_parameters["mimeType"] = "video/x-flv";
                        $data_object_parameters["mediaURL"] = $size["source"];
                        
                        $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
                    }
                }
            }
        }
        
        
        
        $taxon = new SchemaTaxon($taxon_parameters);
        
        return $taxon;
    }
    
    public static function photos_get_sizes($photo_id, $auth_token = "")
    {
        $url = self::generate_rest_url("flickr.photos.getSizes", array("photo_id" => $photo_id, "auth_token" => $auth_token), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function people_get_info($user_id, $auth_token = "")
    {
        $url = self::generate_rest_url("flickr.people.getInfo", array("user_id" => $user_id, "auth_token" => $auth_token), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function people_get_public_photos($user_id, $auth_token = "")
    {
        $url = self::generate_rest_url("flickr.people.getPublicPhotos", array("user_id" => $user_id, "auth_token" => $auth_token), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function photos_get_info($photo_id, $secret, $auth_token = "")
    {
        $url = self::generate_rest_url("flickr.photos.getInfo", array("photo_id" => $photo_id, "secret" => $secret, "auth_token" => $auth_token), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function pools_get_photos($group_id, $machine_tag, $per_page, $page, $auth_token = "")
    {
        $extras = "";
        $url = self::generate_rest_url("flickr.groups.pools.getPhotos", array("group_id" => $group_id, "machine_tags" => $machine_tag, "extras" => $extras, "per_page" => $per_page, "page" => $page, "auth_token" => $auth_token), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function auth_get_frob()
    {
        $url = self::generate_rest_url("flickr.auth.getFrob", array(), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function auth_check_token($auth_token)
    {
        $url = self::generate_rest_url("flickr.auth.checkToken", array("auth_token" => $auth_token), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function auth_get_token($frob)
    {
        $url = self::generate_rest_url("flickr.auth.getToken", array("frob" => $frob), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function photo_url($photo_id, $secret, $server, $farm)
    {
        $photo_url = "http://farm".$farm.".static.flickr.com/".$server."/".$photo_id."_".$secret.".jpg";
        
        // Functions::debug("getting sizes for id: $photo_id\n");
        // $sizes = self::photos_get_sizes($photo_id);
        // if(@$sizes)
        // {
        //     foreach($sizes->sizes->size as $size)
        //     {
        //         $photo_url = $size['source'];
        //     }
        // }
        
        return $photo_url;
    }
    
    public static function valid_auth_token($auth_token)
    {
        $response = self::auth_check_token($auth_token);
        if(@$response->auth->token) return true;
        return false;
    }
    
    public static function login_url()
    {
        $parameters = self::request_parameters(false);
        $parameters["perms"] = "write";
        
        $encoded_parameters = self::encode_parameters($parameters);
        
        return FLICKR_AUTH_PREFIX . implode("&", $encoded_parameters) . "&api_sig=" . self::generate_signature($parameters);
    }
    
    public static function generate_rest_url($method, $params, $sign)
    {
        $parameters = self::request_parameters($method);
        
        foreach($params as $k => $v) $parameters[$k] = $v;
        
        $encoded_paramameters = self::encode_parameters($parameters);
        
        $url = FLICKR_REST_PREFIX.implode("&", $encoded_paramameters);
        
        if($sign) $url.="&api_sig=".self::generate_signature($parameters);
        
        return $url;
    }
    
    public static function encode_parameters($parameters)
    {
        $encoded_paramameters = array();
        foreach($parameters as $k => $v) $encoded_paramameters[] = urlencode($k).'='.urlencode($v);
        return $encoded_paramameters;
    }
    
    public static function request_parameters($method)
    {
        $parameters = array("api_key" => FLICKR_API_KEY);
        if($method) $parameters["method"] = $method;
        
        return $parameters;
    }
    
    public static function generate_signature($parameters)
    {
        $signature = FLICKR_SHARED_SECRET;
        
        ksort($parameters);
        foreach($parameters as $k => $v)
        {
            $signature .= $k.$v;
        }
        
        return md5($signature);
    }
}

?>