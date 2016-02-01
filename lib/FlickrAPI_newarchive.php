<?php
namespace php_active_record;

/* for the Flickr connector */
$GLOBALS['flickr_licenses'] = array();
//$GLOBALS['flickr_licenses'][0] = "All Rights Reserved";
$GLOBALS['flickr_licenses'][1] = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
$GLOBALS['flickr_licenses'][2] = "http://creativecommons.org/licenses/by-nc/2.0/";
//$GLOBALS['flickr_licenses'][3] = "http://creativecommons.org/licenses/by-nc-nd/2.0/";
$GLOBALS['flickr_licenses'][4] = "http://creativecommons.org/licenses/by/2.0/";
$GLOBALS['flickr_licenses'][5] = "http://creativecommons.org/licenses/by-sa/2.0/";
//$GLOBALS['flickr_licenses'][6] = "http://creativecommons.org/licenses/by-nd/2.0/";

$GLOBALS['flickr_cache_path'] = DOC_ROOT . "/update_resources/connectors/files/flickr_cache"; //old cache path
$GLOBALS['flickr_cache_path'] = DOC_ROOT . "/public/tmp/flickr_cache";

define("FLICKR_REST_PREFIX", "http://api.flickr.com/services/rest/?");
define("FLICKR_AUTH_PREFIX", "http://api.flickr.com/services/auth/?");
define("FLICKR_UPLOAD_URL", "http://www.flickr.com/services/upload/");
define("FLICKR_EOL_GROUP_ID", "806927@N20");

class FlickrAPI
{
    public static function get_all_eol_photos($auth_token = "", $resource_file = null)
    {
        self::create_cache_path();
        $all_taxa = array();
        $used_image_ids = array();
        $per_page = 500;
        
        // Get metadata about the EOL Flickr pool
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", 1, 1, $auth_token);
        if($response && isset($response->photos->total))
        {
            $total = $response->photos->total;
            
            // number of API calls to be made
            $total_pages = ceil($total / $per_page);
            
            $taxa = array();
            $total_pages = 20;
            $per_page = 100;
            require_vendor('eol_content_schema_v2');
            $archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/temp/flickr_dwc/"));
            for($i=1 ; $i<=$total_pages ; $i++)
            {
                echo "getting page $i: ".time_elapsed()."\n";
                $page_taxa = self::get_eol_photos($per_page, $i, $auth_token);
                if($page_taxa)
                {
                    foreach($page_taxa as $t)
                    {
                        if($resource_file)
                        {
                            // fwrite($resource_file, $t->__toXML());
                            self::old_to_new_conversion($t, $archive_builder);
                        }else $all_taxa[] = $t;
                    }
                }
            }
            $archive_builder->finalize();
        }
        
        return $all_taxa;
    }
    
    public static function old_to_new_conversion($taxon, $archive_builder)
    {
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon->identifier;
        echo "$t->taxonID\n";
        $t->scientificName = $taxon->scientificName;
        $t->kingdom = @$taxon->kingdom;
        $t->phylum = @$taxon->phylum;
        $t->class = @$taxon->class;
        $t->order = @$taxon->order;
        $t->family = @$taxon->family;
        $t->genus = @$taxon->genus;
        $archive_builder->write_object_to_file($t);
        
        foreach($taxon->dataObjects as $data_object)
        {
            $mr = new \eol_schema\MediaResource();
            $mr->identifier = $data_object->identifier;
            $mr->taxonID = $taxon->identifier;
            $mr->type = $data_object->dataType;
            $mr->format = $data_object->mimeType;
            $mr->title = @$data_object->title;
            $mr->description = @$data_object->description;
            $mr->accessURI = $data_object->mediaURL;
            $mr->thumbnailURL = @$data_object->thumbnailURL;
            $mr->UsageTerms = $data_object->license;
            $mr->CreateDate = @$data_object->created;
            $mr->furtherInformationURL = @$data_object->source;
            $mr->LocationCreated = @$data_object->location;
            if($agent = @$data_object->agents[0])
            {
                $mr->creator = $agent->fullName;
            }
            if($point = @$data_object->point)
            {
                $mr->lat = @$point->latitude;
                $mr->long = @$point->longitude;
                $mr->alt = @$point->altitude;
            }
            $archive_builder->write_object_to_file($mr);
        }
    }
    
    public static function get_eol_photos($per_page, $page, $auth_token = "")
    {
        global $used_image_ids;
        
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", $per_page, $page, $auth_token);
        
        static $count_taxa = 0;
        $page_taxa = array();
        foreach($response->photos->photo as $photo)
        {
            if(@$used_image_ids[$photo->id]) continue;
            $count_taxa++;
            echo "taxon $count_taxa ($photo->id): ".time_elapsed()."\n";
            
            $taxa = self::get_taxa_for_photo($photo->id, $photo->secret, $photo->lastupdate, $auth_token);
            if($taxa)
            {
                foreach($taxa as $t) $page_taxa[] = $t;
            }
            
            $used_image_ids[$photo->id] = true;
        }
        
        return $page_taxa;
    }
    
    public static function get_taxa_for_photo($photo_id, $secret, $last_update, $auth_token = "")
    {
        if($file_json_object = self::check_cache('photosGetInfo', $photo_id, $last_update))
        {
            $photo = @$file_json_object->photo;
        }else
        {
            $photo_response = self::photos_get_info($photo_id, $secret, $auth_token);
            $photo = @$photo_response->photo;
        }
        if(!$photo) debug("\n\nERROR:Photo $photo_id is not available\n\n");
        
        if($photo->visibility->ispublic != 1) return false;
        if($photo->usage->candownload != 1) return false;
        
        if(@!$GLOBALS["flickr_licenses"][$photo->license]) return false;
        
        $parameters = array();
        $parameters["identifier"] = array($photo_id);
        echo "P:$photo_id\n";
        $parameters["subspecies"] = array();
        $parameters["trinomial"] = array();
        $parameters["species"] = array();
        $parameters["scientificName"] = array();
        $parameters["genus"] = array();
        $parameters["family"] = array();
        $parameters["order"] = array();
        $parameters["class"] = array();
        $parameters["phylum"] = array();
        $parameters["kingdom"] = array();
        foreach($photo->tags->tag as $tag)
        {
            $string = trim($tag->raw);
            
            if(preg_match("/^taxonomy:subspecies=(.+)$/i", $string, $arr)) $parameters["subspecies"][] = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:trinomial=(.+)$/i", $string, $arr)) $parameters["trinomial"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:species=(.+)$/i", $string, $arr)) $parameters["species"][] = strtolower(trim($arr[1]));
            elseif(preg_match("/^taxonomy:binomial=(.+ .+)$/i", $string, $arr)) $parameters["scientificName"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:genus=(.+)$/i", $string, $arr)) $parameters["genus"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:family=(.+)$/i", $string, $arr)) $parameters["family"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:order=(.+)$/i", $string, $arr)) $parameters["order"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:class=(.+)$/i", $string, $arr)) $parameters["class"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:phylum=(.+)$/i", $string, $arr)) $parameters["phylum"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:kingdom=(.+)$/i", $string, $arr)) $parameters["kingdom"][] = ucfirst(trim($arr[1]));
            elseif(preg_match("/^taxonomy:common=(.+)$/i", $string, $arr)) $parameters["commonNames"][] = new \SchemaCommonName(array("name" => trim($arr[1])));
        }
        
        $taxon_parameters = array();
        $return_false = false;
        foreach($parameters as $key => $value)
        {
            if(count($value) > 1)
            {
                // there can be more than one common name
                if($key == "commonNames") continue;
                // if there is more than one scientific name disregard all other parameters
                elseif($key == "scientificName")
                {
                    foreach($value as $name)
                    {
                        $taxon_parameters[] = array("scientificName" => $name, 'identifier' => $photo_id);
                    }
                }else $return_false = true;
            }
        }
        // return false if there were multiple rank values, and not multiple scientificNames
        if($return_false && !$taxon_parameters) return false;
        echo "A\n";
        // if there weren't two scientific names it will get here
        if(!$taxon_parameters)
        {
            $temp_params = array();
            foreach($parameters as $key => $value)
            {
                if($key == "commonNames") $temp_params[$key] = $value;
                elseif($value) $temp_params[$key] = $value[0];
                if($key == 'identifier') echo $value[0]."\n";
            }
            
            if(@$temp_params["trinomial"]) $temp_params["scientificName"] = $temp_params["trinomial"];
            if(@!$temp_params["scientificName"] && @$temp_params["genus"] && @$temp_params["species"] && !preg_match("/ /", $temp_params["genus"]) && !preg_match("/ /", $temp_params["species"])) $temp_params["scientificName"] = $temp_params["genus"]." ".$temp_params["species"];
            if(@!$temp_params["genus"] && @preg_match("/^([^ ]+) /", $temp_params["scientificName"], $arr)) $temp_params["genus"] = $arr[1];
            if(@!$temp_params["scientificName"] && @!$temp_params["genus"] && @!$temp_params["family"] && @!$temp_params["order"] && @!$temp_params["class"] && @!$temp_params["phylum"] && @!$temp_params["kingdom"]) return false;
            
            $taxon_parameters[] = $temp_params;
        }
        
        // get the data objects and add them to the parameter arrays
        $data_objects = self::get_data_objects($photo);
        if($data_objects)
        {
            foreach($taxon_parameters as &$p)
            {
                $p["dataObjects"] = $data_objects;
            }
        }else return false;
        
        // turn the parameter arrays into objects to return
        $taxa = array();
        foreach($taxon_parameters as &$p)
        {
            $taxa[] = new \SchemaTaxon($p);
        }
        
        return $taxa;
    }
    
    public static function get_data_objects($photo)
    {
        $data_objects = array();
        
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $photo->id;
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["mimeType"] = "image/jpeg";
        $data_object_parameters["title"] = $photo->title->_content;
        $data_object_parameters["description"] = $photo->description->_content;
        $data_object_parameters["mediaURL"] = self::photo_url($photo->id, $photo->secret, $photo->server, $photo->farm);
        $data_object_parameters["license"] = @$GLOBALS["flickr_licenses"][$photo->license];
        if(isset($photo->dates->taken)) $data_object_parameters["created"] = $photo->dates->taken;
        
        foreach($photo->urls->url as $url)
        {
            if($url->type=="photopage") $data_object_parameters["source"] = $url->_content;
        }
        
        $agent_parameters = array();
        if(trim($photo->owner->realname) != "") $agent_parameters["fullName"] = $photo->owner->realname;
        else $agent_parameters["fullName"] = $photo->owner->username;
        $agent_parameters["homepage"] = "http://www.flickr.com/photos/".$photo->owner->nsid;
        $agent_parameters["role"] = "photographer";
        
        $data_object_parameters["agents"] = array();
        $data_object_parameters["agents"][] = new \SchemaAgent($agent_parameters);
        
        if(@$photo->geoperms->ispublic = 1)
        {
            $geo_point_parameters = array();
            if(isset($photo->location->latitude)) $geo_point_parameters["latitude"] = $photo->location->latitude;
            if(isset($photo->location->longitude)) $geo_point_parameters["longitude"] = $photo->location->longitude;
            if($geo_point_parameters) $data_object_parameters["point"] = new \SchemaPoint($geo_point_parameters);
            
            $locations = array();
            if(isset($photo->location->locality->_content)) $locations[0] = $photo->location->locality->_content;
            if(isset($photo->location->region->_content)) $locations[1] = $photo->location->region->_content;
            if(isset($photo->location->country->_content)) $locations[2] = $photo->location->country->_content;
            
            if($locations) $data_object_parameters["location"] = implode(", ", $locations);
        }
        
        // If the media type is video, there should be a Video Player type linking to the video
        // move the image into the thumbnail and video into mediaURL
        if($photo->media == "video")
        {
            debug("getting sizes for id: ".$photo->id."\n");
            
            $data_object_parameters["thumbnailURL"] = $data_object_parameters["mediaURL"];
            $data_object_parameters["mediaURL"] = NULL;
            if($file_json_object = self::check_cache('photosGetSizes', $photo->id))
            {
                $sizes = $file_json_object;
            }else
            {
                $sizes = self::photos_get_sizes($photo->id);
            }
            
            if(@$sizes)
            {
                foreach($sizes->sizes->size as $size)
                {
                    if($size->label == "Video Player")
                    {
                        $data_object_parameters["identifier"] .= "_video";
                        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/MovingImage";
                        $data_object_parameters["mimeType"] = "video/x-flv";
                        $data_object_parameters["mediaURL"] = $size->source;
                        
                        $data_objects[] = new \SchemaDataObject($data_object_parameters);
                    }
                }
            }
        }else
        {
            // if its not a video, its an image so add it to the list
            $data_objects[] = new \SchemaDataObject($data_object_parameters);
        }
        
        
        return $data_objects;
    }
    
    public static function photos_get_sizes($photo_id, $auth_token = "")
    {
        $url = self::generate_rest_url("flickr.photos.getSizes", array("photo_id" => $photo_id, "auth_token" => $auth_token, "format" => "json", "nojsoncallback" => 1), 1);
        $response = Functions::get_remote_file($url, array('timeout' => 30));
        self::add_to_cache('photosGetSizes', $photo_id, $response);
        return json_decode($response);
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
        $url = self::generate_rest_url("flickr.photos.getInfo", array("photo_id" => $photo_id, "secret" => $secret, "auth_token" => $auth_token, "format" => "json", "nojsoncallback" => 1), 1);
        $response = Functions::get_remote_file($url, array('timeout' => 30));
        self::add_to_cache('photosGetInfo', $photo_id, $response);
        return json_decode($response);
    }
    
    public static function pools_get_photos($group_id, $machine_tag, $per_page, $page, $auth_token = "", $user_id = NULL)
    {
        $extras = "last_update,media,url_o";
        $url = self::generate_rest_url("flickr.groups.pools.getPhotos", array("group_id" => $group_id, "machine_tags" => $machine_tag, "extras" => $extras, "per_page" => $per_page, "page" => $page, "auth_token" => $auth_token, "user_id" => $user_id, "format" => "json", "nojsoncallback" => 1), 1);
        return json_decode(Functions::get_remote_file($url, array('timeout' => 30)));
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
        
        if($file_json_object = self::check_cache('photosGetSizes', $photo_id))
        {
            $sizes = $file_json_object;
        }else
        {
            $sizes = self::photos_get_sizes($photo_id);
        }
        
        if(@$sizes)
        {
            foreach($sizes->sizes->size as $size)
            {
                if(preg_match("/(video|mp4)/i", $size->label)) continue;
                $photo_url = $size->source;
            }
        }
        
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
    
    public static function add_to_cache($dir_name, $photo_id, $photo_response)
    {
        $filter_dir = substr($photo_id, -2);
        $dir_path = $GLOBALS['flickr_cache_path'] . "/$dir_name/$filter_dir";
        $file_path = "$dir_path/$photo_id.json";
        
        // make sure cache directory exists
        if(!file_exists($dir_path)) mkdir($dir_path);
        
        // write to cache file
        if(!($FILE = fopen($file_path, "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$file_path);
          return;
        }
        fwrite($FILE, $photo_response);
        fclose($FILE);
    }
    
    public static function check_cache($dir_name, $photo_id, $last_update = null)
    {
        $filter_dir = substr($photo_id, -2);
        $file_path = $GLOBALS['flickr_cache_path'] . "/$dir_name/$filter_dir/$photo_id.json";
        if(file_exists($file_path))
        {
            $file_contents = file_get_contents($file_path);
            $json_object = json_decode($file_contents);
            // if we're checking the cache for GetInfo and there is a later copy, then
            // delete BOTH the GetInfo and GetSizes calls for that media
            if($dir_name == 'photosGetInfo' && (!$last_update || @$json_object->photo->dates->lastupdate != $last_update))
            {
                unlink($file_path);
                $sizes_path = $GLOBALS['flickr_cache_path'] . "/photosGetSizes/$filter_dir/$photo_id.json";
                @unlink($sizes_path);
            }else return $json_object;
        }
        return false;
    }

    function create_cache_path()
    {
        if(!file_exists($GLOBALS['flickr_cache_path'])) mkdir($GLOBALS['flickr_cache_path']);
        if(!file_exists($GLOBALS['flickr_cache_path']."/photosGetInfo")) mkdir($GLOBALS['flickr_cache_path']."/photosGetInfo");
        if(!file_exists($GLOBALS['flickr_cache_path']."/photosGetSizes")) mkdir($GLOBALS['flickr_cache_path']."/photosGetSizes");
    }

}
?>
