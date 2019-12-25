<?php
namespace php_active_record;

// while working on bhl flickr update DATA ticket
// https://api.flickr.com/services/rest/?&method=flickr.photos.getInfo?api_key=7856957eced5a8ddbad50f1bca0db452&photo_id=2636

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

define("ANDREAS_KAY_ID", "75374522@N06"); // Andreas Kay photostream (DATA-1843) - https://www.flickr.com/photos/andreaskay/ OR https://www.flickr.com/photos/75374522@N06/

// $GLOBALS['flickr_cache_path'] = DOC_ROOT . "/update_resources/connectors/files/flickr_cache";    //old cache path
// $GLOBALS['flickr_cache_path'] = DOC_ROOT . "/public/tmp/flickr_cache";                           //old cache path
$GLOBALS['flickr_cache_path'] = DOC_ROOT . "/" . $GLOBALS['MAIN_CACHE_PATH'] . "flickr_cache";

$GLOBALS['expire_seconds'] = 60*60*24*20; //0 -> expires now, false -> doesn't expire, 60*60*24*30 -> expires in 30 days orig
// $GLOBALS['expire_seconds'] = false; //may use false permanently. Will check again next month to confirm. 
// ON 2ND THOUGHT IT SHOULD ALWAYS BE false. Since mostly only new photos are what we're after. Very seldom, a photo gets updated in its lifetime.
$GLOBALS['expire_seconds'] = 60*60*24*30*3; //maybe quarterly is the way to go moving forward.

// these two variables are used to limit the number of photos per taxon for Flickr photostream resources, if needed (e.g. Smithsonian Wild's photostream)
$GLOBALS['taxa'] = array();
$GLOBALS['max_photos_per_taxon'] = false;

class FlickrAPI
{
    public static function get_all_eol_photos($auth_token = "", $resource_file = null, $user_id = NULL, $start_date = NULL, $end_date = NULL, $resource_id = NULL)
    {
        $GLOBALS['resource_id'] = $resource_id;
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
            for($i=1 ; $i<=$total_pages ; $i++) {
                /* when running 2 or more connectors...
                $m = 265;
                $cont = false;
                if($i >= 1 && $i < $m)      $cont = true;
                if($i >= $m && $i < $m*2)   $cont = true;
                if(!$cont) continue;
                */
                
                echo "getting page $i: ".time_elapsed()."\n";
                $page_taxa = self::get_eol_photos($per_page, $i, $auth_token, $user_id, $start_date, $end_date);
                if($page_taxa) {
                    foreach($page_taxa as $t) {
                        if($resource_file) fwrite($resource_file, $t->__toXML());
                        else $all_taxa[] = $t;
                    }
                }
                // if($i > 2) break; //debug - process just a subset and check the resource file...
            }
        }
        else {
            if(isset($response->stat)) print_r($response);
        }
        
        return $all_taxa;
    }
    
    public static function get_eol_photos($per_page, $page, $auth_token = "", $user_id = NULL, $start_date = NULL, $end_date = NULL)
    {
        global $used_image_ids;
        
        $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", $per_page, $page, $auth_token, $user_id, $start_date, $end_date);
        
        if(isset($response->photos->photo)) {
            echo "\n page " . $response->photos->page . " of " . $response->photos->pages . " | total taxa =  " . $response->photos->total . "\n";
            echo "\n -- response count: " . count($response);
            echo "\n -- response photos count per page: " . count($response->photos->photo) . "\n";
        }
        else {
            echo "\n Access failed. Will try again in 2 minutes";
            sleep(120);
            $response = self::pools_get_photos(FLICKR_EOL_GROUP_ID, "", $per_page, $page, $auth_token, $user_id, $start_date, $end_date);
        }
        
        static $count_taxa = 0;
        $page_taxa = array();
        if(isset($response->photos->photo)) {
            foreach($response->photos->photo as $photo) {
                // print_r($photo); //exit("\nstop1 [".$GLOBALS['resource_id']."]\n"); //good debug
                /*stdClass Object(
                    [id] => 5567584423
                    [owner] => 61021753@N02 -> this is the photostream id. e.g. BHL: BioDivLibrary's photostream - http://www.flickr.com/photos/61021753@N02
                    [secret] => 910fb05c55
                    [server] => 5107
                    [farm] => 6
                    [title] => animalkingdomarr00cuvier_0149
                    [ispublic] => 1
                    [isfriend] => 0
                    [isfamily] => 0
                    [lastupdate] => 1531528495
                    [media] => photo
                    [media_status] => ready
                    [url_o] => https://live.staticflickr.com/5107/5567584423_d681513a3c_o.jpg
                    [height_o] => 3733
                    [width_o] => 2262
                )*/
                
                // /* start: DATA-1843
                if($GLOBALS['resource_id'] == 15) { //meaning regular Flickr resource
                    if($photo->owner == ANDREAS_KAY_ID) continue; //exclude images from this photostream
                }
                // end: DATA-1843 */
                
                if(@$used_image_ids[$photo->id]) continue;
                $count_taxa++;
                if(($count_taxa % 100) == 0) echo "taxon: $count_taxa ($photo->id): ".time_elapsed()."\n";

                $taxa = self::get_taxa_for_photo($photo->id, $photo->secret, $photo->lastupdate, $auth_token, $user_id);
                if($taxa) {
                    foreach($taxa as $t) {
                        // print_r($t); exit("\nstop2 [".$GLOBALS['resource_id']."]\n"); //good debug
                        $page_taxa[] = $t;
                    }
                }

                $used_image_ids[$photo->id] = true;
                // if($count_taxa >= 5) break; //debug - process just a subset and check the resource file...
            }
        }
        else {
            echo "\nAccess failed. Will continue next page or you can stop process and try again later.\n";
            sleep(60);
        }
        return $page_taxa;
    }
    
    public static function get_taxa_for_photo($photo_id, $secret, $last_update, $auth_token = "", $user_id = NULL)
    {
        $GLOBALS['photo_id'] = $photo_id;
        /* good tests when developing: andreas_kay_flickr.php
        if(isset($GLOBALS["func"])) echo "\nremote func OK\n";
        else echo "\nproblem with remote func\n";
        exit("\ntest: ".ANDREAS_KAY_ID."\n");
        */
        
        // /* old orig
        $eli = 0;
        if($file_json_object = self::check_cache('photosGetInfo', $photo_id, $last_update)) {
            $photo = @$file_json_object->photo;
            $eli = 1;
        }
        else {
            $photo_response = self::photos_get_info($photo_id, $secret, $auth_token);
            $photo = @$photo_response->photo;
            $eli = 2;
        }
        // print_r($photo); exit("\nhere $eli\n"); //good debug
        // */

        if(!$photo) debug("\n\nERROR:Photo $photo_id is not available\n\n");
        
        /* ----- start -- good way to debug a photo: where data is coming from and see its actual contents
        if($photo_id == "6070544906") {
            print_r($photo);
            exit("\nlast_update: [$last_update][$eli]\n");
        }
        ---- end */
        
        if($user_id == FLICKR_BHL_ID) $photo->bhl_addtl = self::add_additional_BHL_meta($photo); // https://eol-jira.bibalex.org/browse/DATA-1703
        
        if(@$photo->visibility->ispublic != 1) return false;
        if($photo->usage->candownload != 1) return false;
        
        if(@!$GLOBALS["flickr_licenses"][$photo->license]) return false;
        
        // echo "\nreached 100\n";
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
        
        /* we use this block to test what <taxon> and <dataObject> entries are created for diff. types of tags
        print_r($photo->tags->tag);
        if(@$photo->tags->tag[4]->raw == "taxonomy:binomial=Loligo vulgaris") 
        {
            $photo->tags->tag[4]->raw = "taxonomy:binomial=Loligo vulgaris eli";
            print_r($photo->tags->tag);
        }
        */
        if($user_id == ANDREAS_KAY_ID) {
            if($photo->tags->tag) @$GLOBALS['func']->count['media with machine tags']++;
        }
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
            elseif(preg_match("/^taxonomy:common=(.+)$/i", $string, $arr))  {
                /* DATA-1804 - vernaculars removed from Flickr Group (resource_id = 15) */
                if($GLOBALS['resource_id'] != 15) {
                    $parameters["commonNames"][] = new \SchemaCommonName(array("name" => trim($arr[1])));
                }
            }
            
            /* Photos from Smithsonian photostream that have tag "taxonomy:binomial" is already being shared in the EOL Flickr group.
            So here we will only get those photos with tag "taxonomy:species" only, without "taxonomy:binomial" */
            if($user_id == FLICKR_SMITHSONIAN_ID) {
                if(!preg_match("/^taxonomy:binomial=(.+ .+)$/i", $string, $arr)) {
                    if(preg_match("/^taxonomy:species=(.+ .+)$/i", $string, $arr)) {
                        if(!preg_match("/(unknown|unkown|company|worker|species)/i", $arr[1])) $parameters["scientificName"][] = ucfirst(trim($arr[1]));
                    }
                }
                else continue; // those using taxonomy:binomial supposedly are already in the EOL Flickr group
            }
            
            /* BHL photostream resource should not assign images to synonyms */
            if($user_id == FLICKR_BHL_ID) {
                if(preg_match("/^taxonomy:binomial=(.+ .+)$/i", $string, $arr)) {
                    $sciname = ucfirst(trim($arr[1]));
                    if(self::is_sciname_synonym($sciname))
                    {   //remove value in array: $parameters["scientificName"]
                        debug("\n" . count($parameters["scientificName"]) . "\n");
                        $parameters["scientificName"] = array_diff($parameters["scientificName"], array($sciname));
                        debug("\n[$sciname] is synonym or new name in eol.org");
                        debug("\n" . count($parameters["scientificName"]) . "\n");
                    }
                }
            }

        } //end tags loop

        // /* ------------ start DATA-1843 ------------
        if($user_id == ANDREAS_KAY_ID) {
            // print_r($parameters); exit;
            if(!self::is_there_clear_sciname_in_tags($parameters)) {
                @$GLOBALS['func']->count['lack machine tags']++;
                
                // echo "\nNO sciname\n";
                $parameters = $GLOBALS['func']->AndreasKay_addtl_taxon_assignment($photo->tags->tag, false); //2nd params is $allowsQuestionMarksYN
                if(!$parameters['scientificName']) $parameters = $GLOBALS['func']->AndreasKay_addtl_taxon_assignment($photo->tags->tag, true); //2nd params is $allowsQuestionMarksYN

                // echo "\nFrom Andreas...\n";
                if(!$parameters['scientificName']) {
                    @$GLOBALS['func']->count['media with tags but nothing we can match']++;
                    @$GLOBALS['func']->count['photo_id no sciname'][$GLOBALS['photo_id']] = '';
                    return false;
                }
            }
        }
        // ------------ start DATA-1843 ------------ */
        
        // echo "\nreached 101\n";
        $taxon_parameters = array();
        $return_false = false;
        foreach($parameters as $key => $value) {
            if(count($value) > 1) {
                // there can be more than one common name
                if($key == "commonNames") continue;
                // if there is more than one scientific name disregard all other parameters
                elseif($key == "scientificName") {
                    foreach($value as $name) {
                        if($name) $taxon_parameters[] = array("scientificName" => $name);
                    }
                }else $return_false = true;
            }
        }
        // return false if there were multiple rank values, and not multiple scientificNames
        if($return_false && !$taxon_parameters) return false;
        
        // echo "\nreached 102 return\n";
        // if there weren't two scientific names it will get here
        if(!$taxon_parameters) {
            $temp_params = array();
            foreach($parameters as $key => $value) {
                if($key == "commonNames") $temp_params[$key] = $value;
                elseif(@$value[0]) $temp_params[$key] = $value[0];
            }
            
            if(@$temp_params["trinomial"]) $temp_params["scientificName"] = $temp_params["trinomial"];
            if(@!$temp_params["scientificName"] && @$temp_params["genus"] && @$temp_params["species"] && !preg_match("/ /", $temp_params["genus"]) && !preg_match("/ /", $temp_params["species"])) $temp_params["scientificName"] = $temp_params["genus"]." ".$temp_params["species"];
            if(@!$temp_params["genus"] && @preg_match("/^([^ ]+) /", $temp_params["scientificName"], $arr)) $temp_params["genus"] = $arr[1];
            if(@!$temp_params["scientificName"] && @!$temp_params["genus"] && @!$temp_params["family"] && @!$temp_params["order"] && @!$temp_params["class"] 
                                                && @!$temp_params["phylum"] && @!$temp_params["kingdom"])
            {
                // echo "\nreached 103 return\n";
                return false;
            }
            $taxon_parameters[] = $temp_params;
        }

        if($user_id == FLICKR_SMITHSONIAN_ID) // we need to limit the number of photos per taxon for Smithsonian Wild Photostream
        {
            if($GLOBALS['max_photos_per_taxon']) {
                if($scientificName = @$taxon_parameters[0]["scientificName"]) {
                    if(!@$GLOBALS['taxa'][$scientificName]) $GLOBALS['taxa'][$scientificName] = array();
                    if(count(@$GLOBALS['taxa'][$scientificName]) >= $GLOBALS['max_photos_per_taxon']) {
                        echo "\n Info: " . $scientificName . " has " . $GLOBALS['max_photos_per_taxon'] . " photos now \n";
                        // echo "\nreached 104 return\n";
                        return false;
                    }
                    if(!in_array($photo->id, @$GLOBALS['taxa'][$scientificName])) $GLOBALS['taxa'][$scientificName][] = $photo->id;
                }
            }
        }
        
        // echo "\nreached 105 return\n";
        // get the data objects and add them to the parameter arrays
        $data_objects = self::get_data_objects($photo, $user_id);
        if($data_objects) {
            foreach($taxon_parameters as &$p) {
                $p["dataObjects"] = $data_objects;
            }
        }else return false;
        
        // turn the parameter arrays into objects to return
        $taxa = array();
        foreach($taxon_parameters as &$p) {
            $taxa[] = new \SchemaTaxon($p);
        }
        
        /* we use this block to test what <taxon> and <dataObject> entries are created for diff. types of tags
        if(in_array("Sepia officinalis", $parameters["scientificName"]) && in_array("Loligo vulgaris eli", $parameters["scientificName"]))
        {
            print_r($taxa); 
            exit("\n-test ends-\n");
        }
        */

        /* new Dec 23, 2019. Sets a more specific name from tags -----------------------
        e.g. taxonomy:order=lepidoptera
             taxonomy:class=insecta
        Should get scientificName = 'Insecta', with rank = 'class'
        [0] => SchemaTaxon Object(
                    [identifier] => 
                    [source] => 
                    [kingdom] => 
                    [phylum] => 
                    [class] => Insecta
                    [order] => Lepidoptera
                    [family] => 
                    [scientificName] => 
        */
        $taxa = self::sets_more_specific_name_from_tags($taxa);
        /* ----------------------------------------------------------------------------- */

        /* good debug. We used this block when testing for Andreas Kay resource DATA-1583
        // if($taxa[0]->scientificName == 'Miconia crocea') {
            print_r($taxa);
            exit("\n-test ends-\n");
        // }
        */
        
        return $taxa;
    }
    private static function sets_more_specific_name_from_tags($taxa)
    {   $i = -1;
        foreach($taxa as $t) { $i++;
            if($t->scientificName) {}
            else {
                if($val = $t->genus) {
                    $taxa[$i]->genus = ''; $taxa[$i]->scientificName = $val; $taxa[$i]->rank = 'genus';
                }
                else {
                    if($val = $t->family) {
                        $taxa[$i]->family = ''; $taxa[$i]->scientificName = $val; $taxa[$i]->rank = 'family';
                    }
                    else {
                        if($val = $t->order) {
                            $taxa[$i]->order = ''; $taxa[$i]->scientificName = $val; $taxa[$i]->rank = 'order';
                        }
                        else {
                            if($val = $t->class) {
                                $taxa[$i]->class = ''; $taxa[$i]->scientificName = $val; $taxa[$i]->rank = 'class';
                            }
                            else {
                                if($val = $t->phylum) {
                                    $taxa[$i]->phylum = ''; $taxa[$i]->scientificName = $val; $taxa[$i]->rank = 'phylum';
                                }
                                else {
                                    if($val = $t->kingdom) {
                                        $taxa[$i]->kingdom = ''; $taxa[$i]->scientificName = $val; $taxa[$i]->rank = 'kingdom';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $taxa;
    }
    private static function is_there_clear_sciname_in_tags($p)
    {
        if($p['scientificName']) return true;
        if($p['genus']) return true;
        if($p['family']) return true;
        if($p['order']) return true;
        if($p['class']) return true;
        if($p['phylum']) return true;
        if($p['kingdom']) return true;
        return false;
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
        if(isset($photo->rotation) && $photo->rotation && preg_match("/_o\./", $data_object_parameters["mediaURL"])) {
            $data_object_parameters["additionalInformation"] = '<rotation>'.$photo->rotation.'</rotation>';
        }
        else $data_object_parameters["additionalInformation"] = '';
        
        foreach($photo->urls->url as $url) {
            if($url->type=="photopage") $data_object_parameters["source"] = $url->_content;
        }
        
        $agent_parameters = array();
        if(trim($photo->owner->realname) != "") $agent_parameters["fullName"] = $photo->owner->realname;
        else                                    $agent_parameters["fullName"] = $photo->owner->username;
        $rights_holder = $agent_parameters["fullName"];
        $agent_parameters["homepage"] = "http://www.flickr.com/photos/".$photo->owner->nsid;
        $agent_parameters["role"] = "photographer";
        if($user_id == FLICKR_BHL_ID) $agent_parameters = array(); //no need for BHL Flicker Photostream (544). Based here: https://eol-jira.bibalex.org/browse/DATA-1703?focusedCommentId=61446&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61446
        
        if($user_id == FLICKR_SMITHSONIAN_ID) $data_object_parameters["rightsHolder"] = "Smithsonian Wild";
        else                                  $data_object_parameters["rightsHolder"] = $rights_holder;
        
        $data_object_parameters["agents"] = array();
        $data_object_parameters["agents"][] = new \SchemaAgent($agent_parameters);
        
        if($user_id == FLICKR_BHL_ID) { //https://eol-jira.bibalex.org/browse/DATA-1703
            foreach(@$photo->bhl_addtl['bhl_agents_parameters'] as $agent_parameters) $data_object_parameters["agents"][] = new \SchemaAgent($agent_parameters);
            if($val = @$photo->bhl_addtl['bhl_spatial']) $data_object_parameters["additionalInformation"] .= "<spatial>$val</spatial>";        //http://eol.org/schema/media_extension.xml#spatial
            if($val = @$photo->bhl_addtl['latitude'])    $data_object_parameters["additionalInformation"] .= "<latitude>$val</latitude>";      //http://www.w3.org/2003/01/geo/wgs84_pos#lat
            if($val = @$photo->bhl_addtl['longitude'])   $data_object_parameters["additionalInformation"] .= "<longitude>$val</longitude>";    //http://www.w3.org/2003/01/geo/wgs84_pos#long
        }

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
        if($photo->media == "video") {
            debug("getting sizes for id: ".$photo->id."\n");
            
            $data_object_parameters["thumbnailURL"] = $data_object_parameters["mediaURL"];
            $data_object_parameters["mediaURL"] = NULL;
            if($file_json_object = self::check_cache('photosGetSizes', $photo->id)) {
                $sizes = $file_json_object;
            }
            else {
                $sizes = self::photos_get_sizes($photo->id);
            }
            
            if(@$sizes) {
                foreach($sizes->sizes->size as $size)
                {
                    if($size->label == "Video Player") {
                        $data_object_parameters["identifier"] .= "_video";
                        $data_object_parameters["dataType"] = "http://purl.org/dc/dcmitype/MovingImage";
                        $data_object_parameters["mimeType"] = "video/x-flv";
                        $data_object_parameters["mediaURL"] = $size->source;
                        $data_objects[] = new \SchemaDataObject($data_object_parameters);
                    }
                }
            }
        }
        else {
            // if its not a video, its an image so add it to the list
            $data_objects[] = new \SchemaDataObject($data_object_parameters);
        }
        return $data_objects;
    }
    
    public static function photos_get_sizes($photo_id, $auth_token = "")
    {
        $url = self::generate_rest_url("flickr.photos.getSizes", array("photo_id" => $photo_id, "auth_token" => $auth_token, "format" => "json", "nojsoncallback" => 1), 1);
        // echo "\naaa=[$url]\n"; //debug
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
        // echo "\nbbb=[$url]\n"; //debug
        $response = Functions::lookup_with_cache($url, $download_options);
        self::add_to_cache('photosGetInfo', $photo_id, $response);
        return json_decode($response);
    }
    
    public static function pools_get_photos($group_id, $machine_tag, $per_page, $page, $auth_token = "", $user_id = NULL, $start_date = NULL, $end_date = NULL)
    {
        $extras = "last_update,media,url_o";
        $url = self::generate_rest_url("flickr.groups.pools.getPhotos", array("group_id" => $group_id, "machine_tags" => $machine_tag, "extras" => $extras, "per_page" => $per_page, "page" => $page, "auth_token" => $auth_token, "user_id" => $user_id, "format" => "json", "nojsoncallback" => 1), 1);
        if(in_array($user_id, array(FLICKR_BHL_ID, FLICKR_SMITHSONIAN_ID, ANDREAS_KAY_ID))) {
            /* remove group_id param to get images from photostream, and not only those in the EOL Flickr group */
            $url = self::generate_rest_url("flickr.photos.search", array("machine_tags" => $machine_tag, "extras" => $extras, "per_page" => $per_page, "page" => $page, "auth_token" => $auth_token, "user_id" => $user_id, "license" => "1,2,4,5,7", "privacy_filter" => "1", "sort" => "date-taken-asc", "min_taken_date" => $start_date, "max_taken_date" => $end_date, "format" => "json", "nojsoncallback" => 1), 1);
        }
        // echo "\nccc=[$url]\n"; //debug
        return json_decode(Functions::lookup_with_cache($url, array('timeout' => 30, 'expire_seconds' => $GLOBALS['expire_seconds'], 'resource_id' => 'flickr'))); //expires in 30 days; rsource_id here is just a folder name in cache
    }
    
    public static function auth_get_frob()
    {
        $url = self::generate_rest_url("flickr.auth.getFrob", array(), 1);
        return Functions::get_hashed_response($url);
    }
    
    public static function auth_check_token($auth_token)
    {
        /* this is deliberately designed to not cache request */
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
        if($file_json_object = self::check_cache('photosGetSizes', $photo_id)) {
            $sizes = $file_json_object;
        }else {
            $sizes = self::photos_get_sizes($photo_id);
        }
        
        if(@$sizes) {
            foreach($sizes->sizes->size as $size) {
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
        foreach($parameters as $k => $v) {
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
        if(!($FILE = Functions::file_open($file_path, "w+"))) return;
        fwrite($FILE, $photo_response);
        fclose($FILE);
    }
    
    public static function check_cache($dir_name, $photo_id, $last_update = null)
    {
        $filter_dir = substr($photo_id, -2);
        $file_path = $GLOBALS['flickr_cache_path'] . "/$dir_name/$filter_dir/$photo_id.json";
        if(file_exists($file_path)) {
            $file_contents = file_get_contents($file_path);
            $json_object = json_decode($file_contents);
            // if we're checking the cache for GetInfo and there is a later copy, then
            // delete BOTH the GetInfo and GetSizes calls for that media
            if($dir_name == 'photosGetInfo' && (!$last_update || @$json_object->photo->dates->lastupdate != $last_update)) {
                unlink($file_path);
                $sizes_path = $GLOBALS['flickr_cache_path'] . "/photosGetSizes/$filter_dir/$photo_id.json";
                @unlink($sizes_path);
            }else return $json_object;
        }
        return false;
    }

    public static function is_sciname_synonym($sciname)
    {
        $expire_seconds = false;
        
        /* debug
        if($sciname == "Falco chrysaetos") $expire_seconds = true;
        else                               $expire_seconds = false;
        */
        
        /*              http://eol.org/api/search/1.0.xml?q=Xanthopsar+flavus&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl= */
        $search_call = "http://eol.org/api/search/1.0.xml?q=" . $sciname .  "&page=1&exact=false&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=";
        if($xml = Functions::lookup_with_cache($search_call, array('timeout' => 30, 'expire_seconds' => $expire_seconds, 'resource_id' => 'eol_api'))) //resource_id here is just a folder name in cache
        {
            $xml = simplexml_load_string($xml);
            $sciname = Functions::canonical_form($sciname);
            if($sciname == Functions::canonical_form(@$xml->entry[0]->title)) return false; //sciname is not a synonym but accepted name
            else {
                $titles = array();
                foreach($xml->entry as $entry) $titles[] = Functions::canonical_form($entry->title);
                if(in_array($sciname, $titles)) return false; //sciname is not a synonym but accepted name
                else return true;
            }
        }
        return false;
    }

    public static function get_photostream_photos($auth_token = "", $resource_file = null, $user_id = NULL, $start_year = NULL, $months_to_be_broken_down = NULL, 
                                                  $max_photos_per_taxon = NULL, $resource_id = NULL)
    {
        if($user_id == FLICKR_BHL_ID) {
            $file = CONTENT_RESOURCE_LOCAL_PATH.'bhl_images_with_box_coordinates.txt';
            if(file_exists($file)) unlink($file);
        }
        
        if($user_id == ANDREAS_KAY_ID) {
            require_library('connectors/CachingTemplateAPI_AndreasKay');
            $GLOBALS['func'] = new CachingTemplateAPI_AndreasKay($resource_id);
            $GLOBALS['func']->initialize_report();
            echo ("\ncorrect...\n");
            /* comment in real operation. Only used during development. Specifically when running "test***" in andreas_kay_flickr.php.
            return;
            */
        }
        if($max_photos_per_taxon) $GLOBALS['max_photos_per_taxon'] = $max_photos_per_taxon;
        $all_taxa = array();
        $date_range = self::get_date_ranges($start_year);
        // print_r($date_range);
        echo "\n count: " . count($date_range);
        if($months_to_be_broken_down) {
            foreach($months_to_be_broken_down as $date) $date_range = array_merge($date_range, self::get_date_ranges($date["year"], $date["month"]));
            echo "\n count: " . count($date_range);
        }
        // print_r($date_range); exit;
        $i = 0; //debug only
        foreach($date_range as $range) { $i++;
            echo "\n\n From: " . $range["start"] . " To: " . $range["end"] . "\n";
            $taxa = self::get_all_eol_photos($auth_token, $resource_file, $user_id, $range["start_timestamp"], $range["end_timestamp"], $resource_id);
            $all_taxa = array_merge($all_taxa, $taxa);
            // if($i > 5) break; //debug - process just a subset and check the resource file...
        }
        ksort($GLOBALS['taxa']);
        print_r($GLOBALS['taxa']);
        return $all_taxa;
    }

    public static function get_date_ranges($start_year, $month = NULL)
    {
        $range = array();
        if(!$month) {
            $current_year = date("Y");
            for ($year = $start_year; $year <= $current_year; $year++) {
                if($year == $current_year) $month_limit = date("n");
                else $month_limit = 12;
                for ($month = 1; $month <= $month_limit; $month++) {
                    $start_date = $year . "-" . Functions::format_number_with_leading_zeros($month, 2) . "-01";
                    $end_date = $year . "-" . Functions::format_number_with_leading_zeros($month, 2) . "-31";
                    $range[] = self::get_timestamp_range($start_date, $end_date);
                }
            }
        }
        else
        {
            $month = Functions::format_number_with_leading_zeros($month, 2);
            for ($day = 1; $day <= 30; $day++) {
                $start_date = $start_year . "-" . $month . "-" . Functions::format_number_with_leading_zeros($day, 2);
                $end_date = $start_year . "-" . $month . "-" . Functions::format_number_with_leading_zeros($day+1, 2);
                $range[] = self::get_timestamp_range($start_date, $end_date);
            }
            if($month == "12") { // last day of the month to first day of the next month
                $next_year = $start_year + 1;
                $next_month = "01";
            }
            else {
                $next_year = $start_year;
                $next_month = Functions::format_number_with_leading_zeros(intval($month) + 1, 2);
            } 
            $start_date = $start_year . "-" . $month . "-31";
            $end_date = $next_year . "-" . $next_month . "-01";
            $range[] = self::get_timestamp_range($start_date, $end_date);
        }
        return $range;
    }

    public static function get_timestamp_range($start_date, $end_date)
    {
        $date_start = new \DateTime($start_date);
        $date_end = new \DateTime($end_date);
        return array("start" => $start_date, "end" => $end_date, "start_timestamp" => $date_start->getTimestamp(), "end_timestamp" => $date_end->getTimestamp());
    }
    
    public static function create_cache_path()
    {
        if(!file_exists($GLOBALS['flickr_cache_path'])) mkdir($GLOBALS['flickr_cache_path']);
        if(!file_exists($GLOBALS['flickr_cache_path']."/photosGetInfo")) mkdir($GLOBALS['flickr_cache_path']."/photosGetInfo");
        if(!file_exists($GLOBALS['flickr_cache_path']."/photosGetSizes")) mkdir($GLOBALS['flickr_cache_path']."/photosGetSizes");
    }
    
    //========== additional BHL functions: per https://eol-jira.bibalex.org/browse/DATA-1703 ===============================================
    private static function create_bhl_spatial($tags)
    {
        if($val = @$tags['geo:locality']) return $val;
        if($val = @$tags['geo:county']) return $val;
        if($val = @$tags['geo:state']) return $val;
        if($val = @$tags['geo:country']) return $val;
        if($val = @$tags['geo:continent']) return $val;
    }
    private static function create_bhl_agent_parameters($tags)
    {
        $agents_parameters = array();
        $jobs = array("artist", "author", "engraver");
        foreach($jobs as $job) {
            $agent_parameters = array();
            if($name = @$tags["$job:name"]) {
                $agent_parameters["fullName"] = $name;
                if($viaf = @$tags["$job:viaf"])     $agent_parameters["homepage"] = "http://www.viaf.org/viaf/".$viaf."/";
                elseif($val = @$tags['bhl:page'])   $agent_parameters["homepage"] = "http://biodiversitylibrary.org/page/".$val;
                $agent_parameters["role"] = "creator";
                $agents_parameters[] = $agent_parameters;
            }
        }
        return $agents_parameters;
    }
    public static function add_additional_BHL_meta($p) //per https://eol-jira.bibalex.org/browse/DATA-1703
    {
        $final = array();
        if($p) {
            $tags = self::get_all_tags($p);
            $final['bhl_agents_parameters'] = self::create_bhl_agent_parameters($tags);
            if($val = self::create_bhl_spatial($tags)) $final['bhl_spatial'] = $val;    //http://eol.org/schema/media_extension.xml#spatial
            if($val = @$tags['geo:lat']) $final['latitude'] = $val;                     //http://www.w3.org/2003/01/geo/wgs84_pos#lat
            if($val = @$tags['geo:lon']) $final['longitude'] = $val;                    //http://www.w3.org/2003/01/geo/wgs84_pos#long
            self::save_bhl_photo_id_with_box_coordinates($p);
        }
        return $final;
    }
    private static function save_bhl_photo_id_with_box_coordinates($p)
    {
        $with_box = false;
        foreach(@$p->notes->note as $note) {
            if(@$note->x && @$note->y && @$note->w && @$note->h) $with_box = true;
        }
        if($with_box) {
            if(!($FILE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH.'bhl_images_with_box_coordinates.txt', "a"))) return;
            fwrite($FILE, $p->id."\n");
            fclose($FILE);
        }
    }
    private static function get_all_tags($p)
    {
        $tags = array();
        foreach($p->tags->tag as $tag) {
            $arr = explode("=", @$tag->raw);
            if($val = @$arr[1]) $tags[$arr[0]] = $val;
        }
        return $tags;
        /* sample output for photo_id = 6070544906
        Array (
            [bhl:page] => 30029640
            [dc:identifier] => http://biodiversitylibrary.org/page/30029640
            [artist:name] => Edward Donovan
            [taxonomy:common] => Weevil
            [taxonomy:binomial] => Curculio bachus
            [author:name] => Edward Donovan
            [author:viaf] => 15714278
            [artist:viaf] => 15714278
            [geo:locality] => Great Britain
        ) */
    }
    //========== end =======================================================================================================================

}

?>
