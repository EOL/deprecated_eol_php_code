<?php
namespace php_active_record;

/* See http://www.flickr.com/services/api/flickr.photos.licenses.getInfo.html */
$GLOBALS['flickr_licenses'] = array();
//$GLOBALS['flickr_licenses'][0] = "All Rights Reserved";
$GLOBALS['flickr_licenses'][1] = "http://creativecommons.org/licenses/by-nc-sa/2.0/";
$GLOBALS['flickr_licenses'][2] = "http://creativecommons.org/licenses/by-nc/2.0/";
//$GLOBALS['flickr_licenses'][3] = "http://creativecommons.org/licenses/by-nc-nd/2.0/";
$GLOBALS['flickr_licenses'][4] = "http://creativecommons.org/licenses/by/2.0/";
$GLOBALS['flickr_licenses'][5] = "http://creativecommons.org/licenses/by-sa/2.0/";
//$GLOBALS['flickr_licenses'][6] = "http://creativecommons.org/licenses/by-nd/2.0/";
$GLOBALS['flickr_licenses'][7] = "http://www.flickr.com/commons/usage/";

define("FLICKR_REST_PREFIX", "https://api.flickr.com/services/rest/?");
define("FLICKR_AUTH_PREFIX", "https://api.flickr.com/services/auth/?");
define("FLICKR_UPLOAD_URL", "https://www.flickr.com/services/upload/");
define("FLICKR_EOL_GROUP_ID", "806927@N20");
define("FLICKR_BHL_ID", "61021753@N02"); // BHL: BioDivLibrary's photostream - http://www.flickr.com/photos/61021753@N02
define("FLICKR_SMITHSONIAN_ID", "51045845@N08"); // Smithsonian Wild's photostream - http://www.flickr.com/photos/51045845@N08
define("OPENTREE_ID", "92803392@N02"); // OpenTree photostream - http://www.flickr.com/photos/92803392@N02 - no resource here, just a way to get all images for a certain Flickr user e.g EFB-1126

$GLOBALS['flickr_cache_path'] = DOC_ROOT . "/update_resources/connectors/files/flickr_cache"; //old cache path
$GLOBALS['flickr_cache_path'] = DOC_ROOT . "/public/tmp/flickr_cache";
$GLOBALS['expire_seconds'] = 172800; //0 -> expires now, false -> doesn't expire, 172800 -> expires in 2 days

// these two variables are used to limit the number of photos per taxon for Flickr photostream resources, if needed (e.g. Smithsonian Wild's photostream)
$GLOBALS['taxa'] = array();
$GLOBALS['max_photos_per_taxon'] = false;

class FlickrAPI
{
    public static function get_all_eol_photos($auth_token = "", $resource_file = null, $user_id = NULL, $start_date = NULL, $end_date = NULL)
    {
        self::create_cache_path();
        $all_taxa = array();
        $used_image_ids = array();
        $per_page = 500;
        
        // Get metadata about the EOL Flickr pool
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", 1, 1, $auth_token, $user_id, $start_date, $end_date);
        if($response && isset($response->photos->total))
        {
            $total = $response->photos->total;
            
            // number of API calls to be made
            $total_pages = ceil($total / $per_page);
            
            $taxa = array();
            for($i=1 ; $i<=$total_pages ; $i++)
            {
                echo "getting page $i: ".time_elapsed()."\n";
                $page_taxa = self::get_eol_photos($per_page, $i, $auth_token, $user_id, $start_date, $end_date);
                if($page_taxa)
                {
                    foreach($page_taxa as $t)
                    {
                        if($resource_file) fwrite($resource_file, $t->__toXML());
                        else $all_taxa[] = $t;
                    }
                }
            }
        }
        else
        {
            if(isset($response->stat)) print_r($response);
        }
        
        return $all_taxa;
    }
    
    public static function get_eol_photos($per_page, $page, $auth_token = "", $user_id = NULL, $start_date = NULL, $end_date = NULL)
    {
        global $used_image_ids;
        
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", $per_page, $page, $auth_token, $user_id, $start_date, $end_date);
        
        if(isset($response->photos->photo))
        {
            echo "\n page " . $response->photos->page . " of " . $response->photos->pages . " | total taxa =  " . $response->photos->total . "\n";
            echo "\n -- response count: " . count($response);
            echo "\n -- response photos count per page: " . count($response->photos->photo) . "\n";
        }
        else
        {
            echo "\n Access failed. Will try again in 2 minutes";
            sleep(120);
            $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", $per_page, $page, $auth_token, $user_id, $start_date, $end_date);
        }
        
        static $count_taxa = 0;
        $page_taxa = array();
        if(isset($response->photos->photo))
        {
            foreach($response->photos->photo as $photo)
            {
                if(@$used_image_ids[$photo->id]) continue;
                $count_taxa++;
                echo "taxon $count_taxa ($photo->id): ".time_elapsed()."\n";

                $taxa = self::get_taxa_for_photo($photo->id, $photo->secret, $photo->lastupdate, $auth_token, $user_id);
                if($taxa)
                {
                    foreach($taxa as $t) $page_taxa[] = $t;
                }

                $used_image_ids[$photo->id] = true;
            }
        }
        else
        {
            echo "\nAccess failed. Will continue next page or you can stop process and try again later.\n";
            sleep(60);
        }
        return $page_taxa;
    }
    
    public static function get_taxa_for_photo($photo_id, $secret, $last_update, $auth_token = "", $user_id = NULL)
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
        
        if(@$photo->visibility->ispublic != 1) return false;
        if($photo->usage->candownload != 1) return false;
        
        if(@!$GLOBALS["flickr_licenses"][$photo->license]) return false;
        
        $parameters = array();
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
            
            /* Photos from Smithsonian photostream that have tag "taxonomy:binomial" is already being shared in the EOL Flickr group.
            So here we will only get those photos with tag "taxonomy:species" only, without "taxonomy:binomial" */
            if($user_id == FLICKR_SMITHSONIAN_ID)
            {
                if(!preg_match("/^taxonomy:binomial=(.+ .+)$/i", $string, $arr))
                {
                    if(preg_match("/^taxonomy:species=(.+ .+)$/i", $string, $arr))
                    {
                        if(!preg_match("/(unknown|unkown|company|worker|species)/i", $arr[1])) $parameters["scientificName"][] = ucfirst(trim($arr[1]));
                    }
                }
                else continue; // those using taxonomy:binomial supposedly are already in the EOL Flickr group
            }
            
            /* BHL photostream resource should not assign images to synonyms */
            if($user_id == FLICKR_BHL_ID)
            {
                if(preg_match("/^taxonomy:binomial=(.+ .+)$/i", $string, $arr))
                {
                    $sciname = ucfirst(trim($arr[1]));
                    if(self::is_sciname_synonym($sciname))
                    {   //remove value in array: $parameters["scientificName"]
                        echo "\n" . count($parameters["scientificName"]) . "\n";
                        $parameters["scientificName"] = array_diff($parameters["scientificName"], array($sciname));
                        echo "\n[$sciname] is synonym or new name in eol.org\n";
                        echo "\n" . count($parameters["scientificName"]) . "\n";
                    }
                }
            }

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
                        if($name) $taxon_parameters[] = array("scientificName" => $name);
                    }
                }else $return_false = true;
            }
        }
        // return false if there were multiple rank values, and not multiple scientificNames
        if($return_false && !$taxon_parameters) return false;
        
        // if there weren't two scientific names it will get here
        if(!$taxon_parameters)
        {
            $temp_params = array();
            foreach($parameters as $key => $value)
            {
                if($key == "commonNames") $temp_params[$key] = $value;
                elseif(@$value[0]) $temp_params[$key] = $value[0];
            }
            
            if(@$temp_params["trinomial"]) $temp_params["scientificName"] = $temp_params["trinomial"];
            if(@!$temp_params["scientificName"] && @$temp_params["genus"] && @$temp_params["species"] && !preg_match("/ /", $temp_params["genus"]) && !preg_match("/ /", $temp_params["species"])) $temp_params["scientificName"] = $temp_params["genus"]." ".$temp_params["species"];
            if(@!$temp_params["genus"] && @preg_match("/^([^ ]+) /", $temp_params["scientificName"], $arr)) $temp_params["genus"] = $arr[1];
            if(@!$temp_params["scientificName"] && @!$temp_params["genus"] && @!$temp_params["family"] && @!$temp_params["order"] && @!$temp_params["class"] && @!$temp_params["phylum"] && @!$temp_params["kingdom"]) return false;
            
            $taxon_parameters[] = $temp_params;
        }

        if($user_id == FLICKR_SMITHSONIAN_ID) // we need to limit the number of photos per taxon for Smithsonian Wild Photostream
        {
            if($GLOBALS['max_photos_per_taxon'])
            {
                if($scientificName = @$taxon_parameters[0]["scientificName"])
                {
                    if(!@$GLOBALS['taxa'][$scientificName]) $GLOBALS['taxa'][$scientificName] = array();
                    if(count(@$GLOBALS['taxa'][$scientificName]) >= $GLOBALS['max_photos_per_taxon']) 
                    {
                        echo "\n Info: " . $scientificName . " has " . $GLOBALS['max_photos_per_taxon'] . " photos now \n";
                        return false;
                    }
                    if(!in_array($photo->id, @$GLOBALS['taxa'][$scientificName])) $GLOBALS['taxa'][$scientificName][] = $photo->id;
                }
            }
        }
        
        // get the data objects and add them to the parameter arrays
        $data_objects = self::get_data_objects($photo, $user_id);
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
    
    public static function get_data_objects($photo, $user_id)
    {
        $data_objects = array();
        
        $data_object_parameters = array();
        $data_object_parameters["identifier"] = $photo->id;
        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/StillImage";
        $data_object_parameters["mimeType"] = Functions::get_mimetype($photo->originalformat, true);
        $data_object_parameters["title"] = $photo->title->_content;
        $data_object_parameters["description"] = $photo->description->_content;
        $data_object_parameters["mediaURL"] = self::photo_url($photo->id, $photo->secret, $photo->server, $photo->farm);
        $data_object_parameters["license"] = @$GLOBALS["flickr_licenses"][$photo->license];
        $data_object_parameters["language"] = 'en';
        if(isset($photo->dates->taken)) $data_object_parameters["created"] = $photo->dates->taken;
        // only the original forms need rotation
        if(isset($photo->rotation) && $photo->rotation && preg_match("/_o\./", $data_object_parameters["mediaURL"]))
        {
            $data_object_parameters["additionalInformation"] = '<rotation>'.$photo->rotation.'</rotation>';
        }
        
        foreach($photo->urls->url as $url)
        {
            if($url->type=="photopage") $data_object_parameters["source"] = $url->_content;
        }
        
        $agent_parameters = array();
        if(trim($photo->owner->realname) != "") $agent_parameters["fullName"] = $photo->owner->realname;
        else $agent_parameters["fullName"] = $photo->owner->username;
        $agent_parameters["homepage"] = "http://www.flickr.com/photos/".$photo->owner->nsid;
        $agent_parameters["role"] = "photographer";
        
        if($user_id == FLICKR_SMITHSONIAN_ID) $data_object_parameters["rightsHolder"] = "Smithsonian Wild";
        else $data_object_parameters["rightsHolder"] = $agent_parameters["fullName"];
        
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
        $response = Functions::lookup_with_cache($url, array('timeout' => 30, 'expire_seconds' => $GLOBALS['expire_seconds']));
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
    
    public static function photos_get_info($photo_id, $secret, $auth_token = "", $download_options = array('timeout' => 30, 'resource_id' => 'flickr')) // this is also being called by FlickrUserAlbumAPI.php
    {
        $download_options['expire_seconds'] = $GLOBALS['expire_seconds'];
        $url = self::generate_rest_url("flickr.photos.getInfo", array("photo_id" => $photo_id, "secret" => $secret, "auth_token" => $auth_token, "format" => "json", "nojsoncallback" => 1), 1);
        $response = Functions::lookup_with_cache($url, $download_options);
        self::add_to_cache('photosGetInfo', $photo_id, $response);
        return json_decode($response);
    }
    
    public static function pools_get_photos($group_id, $machine_tag, $per_page, $page, $auth_token = "", $user_id = NULL, $start_date = NULL, $end_date = NULL)
    {
        $extras = "last_update,media,url_o";
        $url = self::generate_rest_url("flickr.groups.pools.getPhotos", array("group_id" => $group_id, "machine_tags" => $machine_tag, "extras" => $extras, "per_page" => $per_page, "page" => $page, "auth_token" => $auth_token, "user_id" => $user_id, "format" => "json", "nojsoncallback" => 1), 1);
        if(in_array($user_id, array(FLICKR_BHL_ID, FLICKR_SMITHSONIAN_ID)))
        {
            /* remove group_id param to get images from photostream, and not only those in the EOL Flickr group */
            $url = self::generate_rest_url("flickr.photos.search", array("machine_tags" => $machine_tag, "extras" => $extras, "per_page" => $per_page, "page" => $page, "auth_token" => $auth_token, "user_id" => $user_id, "license" => "1,2,4,5,7", "privacy_filter" => "1", "sort" => "date-taken-asc", "min_taken_date" => $start_date, "max_taken_date" => $end_date, "format" => "json", "nojsoncallback" => 1), 1);
        }
        return json_decode(Functions::lookup_with_cache($url, array('timeout' => 30, 'expire_seconds' => $GLOBALS['expire_seconds'], 'resource_id' => 'flickr'))); //expires in 2 days, since Flickr is called every day as Cron task anyway. And resource_id here is just a folder name in cache
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
        
        /* will replace original if needed
        $response = Functions::lookup_with_cache($url);
        return simplexml_load_string($response);
        */
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
        $url = str_ireplace("http://", "https://", $url);
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

    private function is_sciname_synonym($sciname)
    {
        /* http://eol.org/api/search/1.0.xml?q=Xanthopsar+flavus&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl= */
        $search_call = "http://eol.org/api/search/1.0.xml?q=" . $sciname . "&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=";
        if($xml = Functions::lookup_with_cache($search_call, array('timeout' => 30, 'expire_seconds' => false, 'resource_id' => 'eol_api'))) //resource_id here is just a folder name in cache
        {
            $xml = simplexml_load_string($xml);
            $sciname = Functions::canonical_form($sciname);
            if($sciname == Functions::canonical_form(@$xml->entry[0]->title)) return false; //sciname is not a synonym but accepted name
            else
            {
                $titles = array();
                foreach($xml->entry as $entry) $titles[] = Functions::canonical_form($entry->title);
                if(in_array($sciname, $titles)) return false; //sciname is not a synonym but accepted name
                else return true;
            }
        }
        return false;
    }

    public static function get_photostream_photos($auth_token = "", $resource_file = null, $user_id = NULL, $start_year = NULL, $months_to_be_broken_down = NULL, $max_photos_per_taxon = NULL)
    {
        if($max_photos_per_taxon) $GLOBALS['max_photos_per_taxon'] = $max_photos_per_taxon;
        $all_taxa = array();
        $date_range = self::get_date_ranges($start_year);
        echo "\n count: " . count($date_range);
        if($months_to_be_broken_down)
        {
            foreach($months_to_be_broken_down as $date) $date_range = array_merge($date_range, self::get_date_ranges($date["year"], $date["month"]));
            echo "\n count: " . count($date_range);
        }
        foreach($date_range as $range)
        {
            echo "\n\n From: " . $range["start"] . " To: " . $range["end"] . "\n";
            $taxa = self::get_all_eol_photos($auth_token, $resource_file, $user_id, $range["start_timestamp"], $range["end_timestamp"]);
            $all_taxa = array_merge($all_taxa, $taxa);
        }
        ksort($GLOBALS['taxa']);
        print_r($GLOBALS['taxa']);
        return $all_taxa;
    }

    private function get_date_ranges($start_year, $month = NULL)
    {
        $range = array();
        if(!$month)
        {
            $current_year = date("Y");
            for ($year = $start_year; $year <= $current_year; $year++)
            {
                if($year == $current_year) $month_limit = date("n");
                else $month_limit = 12;
                for ($month = 1; $month <= $month_limit; $month++)
                {
                    $start_date = $year . "-" . Functions::format_number_with_leading_zeros($month, 2) . "-01";
                    $end_date = $year . "-" . Functions::format_number_with_leading_zeros($month, 2) . "-31";
                    $range[] = self::get_timestamp_range($start_date, $end_date);
                }
            }
        }
        else
        {
            $month = Functions::format_number_with_leading_zeros($month, 2);
            for ($day = 1; $day <= 30; $day++)
            {
                $start_date = $start_year . "-" . $month . "-" . Functions::format_number_with_leading_zeros($day, 2);
                $end_date = $start_year . "-" . $month . "-" . Functions::format_number_with_leading_zeros($day+1, 2);
                $range[] = self::get_timestamp_range($start_date, $end_date);
            }
            if($month == "12") // last day of the month to first day of the next month
            {
                $next_year = $start_year + 1;
                $next_month = "01";
            }
            else
            {
                $next_year = $start_year;
                $next_month = Functions::format_number_with_leading_zeros(intval($month) + 1, 2);
            } 
            $start_date = $start_year . "-" . $month . "-31";
            $end_date = $next_year . "-" . $next_month . "-01";
            $range[] = self::get_timestamp_range($start_date, $end_date);
        }
        return $range;
    }

    private function get_timestamp_range($start_date, $end_date)
    {
        $date_start = new \DateTime($start_date);
        $date_end = new \DateTime($end_date);
        return array("start" => $start_date, "end" => $end_date, "start_timestamp" => $date_start->getTimestamp(), "end_timestamp" => $date_end->getTimestamp());
    }
    
    function create_cache_path()
    {
        if(!file_exists($GLOBALS['flickr_cache_path'])) mkdir($GLOBALS['flickr_cache_path']);
        if(!file_exists($GLOBALS['flickr_cache_path']."/photosGetInfo")) mkdir($GLOBALS['flickr_cache_path']."/photosGetInfo");
        if(!file_exists($GLOBALS['flickr_cache_path']."/photosGetSizes")) mkdir($GLOBALS['flickr_cache_path']."/photosGetSizes");
    }

}

?>
