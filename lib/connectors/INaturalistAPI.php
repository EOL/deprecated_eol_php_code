<?php
namespace php_active_record;
/* connector: [inat] DATA-1594
Connector loops to the resource collections using the API and compiles all the object page URLs (using the object API), 
the source media URLs and the iNat observation URLs.
*/

class INaturalistAPI
{
    function __construct()
    {
        $this->url["eol_collection"] = "http://eol.org/api/collections/1.0/36789.json?filter=images&sort_by=recently_added&sort_field=&cache_ttl=";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/";
        $this->download_options = array("download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1, "delay_in_minutes" => 1);
        $this->dump_file = CONTENT_RESOURCE_LOCAL_PATH . "iNat_EOL_object_urls.txt";
    }

    function generate_link_backs()
    {
        self::initialize_dump();
        $page = 1; //orig value is 1
        $per_page = 50;
        while(true)
        {
            $url = $this->url["eol_collection"] . "&page=$page&per_page=$per_page";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $missing_iNat_photo_ids = self::get_missing_iNat_photo_ids();
                $collections = json_decode($json);
                $total_col = count($collections->collection_items);
                if($total_col == 0) break; //end of collections
                echo "\ntotal collections = $total_col\n";
                $k = 0;
                foreach($collections->collection_items as $col)
                {
                    $k++; echo "\n page:$page | item $k of $total_col -- ";
                    $rec = array();
                    if($col->object_type == "Image")
                    {
                        $rec['object_id'] = $col->object_id;
                        if($json = Functions::lookup_with_cache($this->url["eol_object"] . $col->object_id . ".json?cache_ttl=", $this->download_options))
                        {
                            $object = json_decode($json);
                            $rec["photo_url"] = @$object->dataObjects[0]->source; //iNat photo page url
                            
                            /* check if photo id is inaccessible */
                            $photo_id = self::get_iNat_photo_id($rec["photo_url"]);
                            if(in_array($photo_id, $missing_iNat_photo_ids)) continue;
                            
                            if($val = self::get_inat_observation_url($rec["photo_url"])) $rec["observation_url"] = $val;
                        }
                    }
                    if($rec)
                    {
                        if(!($h = fopen($this->dump_file, 'a')))
                        {
                          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $this->dump_file);
                          return;
                        }
                        fwrite($h, "http://eol.org/data_objects/".$rec['object_id'] . "\t" . $rec['photo_url'] . "\t" . $rec['observation_url'] . "\n");
                        fclose($h);
                    }
                }
            }
            $page++;
            // if($page > 1) break; //debug
        }//while
    }
    
    private function get_inat_observation_url($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options))
        {
            if(preg_match("/\"http:\/\/www.inaturalist.org\/observations\/(.*?)\"/ims", $html, $arr)) return "http://www.inaturalist.org/observations/" . $arr[1];
        }
        else
        {
            if($photo_id = self::get_iNat_photo_id($url))
            {
                if(!($h = fopen(DOC_ROOT . "/temp/iNat_photoID_not_found.txt", 'a')))
                {
                  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT . "/temp/iNat_photoID_not_found.txt");
                  return;
                }
                fwrite($h, $photo_id . "-");
                fclose($h);
            }
        }
        return false;
    }
    
    private function get_iNat_photo_id($url)
    {
        if(preg_match("/\/photos\/(.*?)xxx/ims", $url."xxx", $arr)) return $arr[1];
        else return false;
    }
    
    private function get_missing_iNat_photo_ids()
    {
        $file = DOC_ROOT . "/temp/iNat_photoID_not_found.txt";
        if(!file_exists($file)) return array();
        $contents = file_get_contents($file);
        $ids = explode("-", $contents);
        print_r($ids);
        return $ids;
    }
    
    private function initialize_dump()
    {
        if(!($h = fopen($this->dump_file, 'w')))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->dump_file);
          return;
        }
        fclose($h);
    }

}
?>