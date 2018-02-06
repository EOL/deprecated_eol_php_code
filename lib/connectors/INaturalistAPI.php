<?php
namespace php_active_record;
/* connector: [inat] DATA-1594
Connector loops to the resource collections using the API and compiles all the object page URLs (using the object API), 
the source media URLs and the iNat observation URLs.
*/

class INaturalistAPI
{
    function __construct($collection_id)
    {
        $this->url["eol_collection"] = "http://eol.org/api/collections/1.0/".$collection_id.".json?filter=images&sort_by=recently_added&sort_field=&cache_ttl=";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/";
        $this->download_options = array("download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1, "delay_in_minutes" => 1);
        $this->download_options['expire_seconds'] = false;
        $this->dump_file = CONTENT_RESOURCE_LOCAL_PATH . "iNat_EOL_object_urls.txt";
    }

    function generate_link_backs()
    {
        self::initialize_dump();
        self::start_process();
        self::zip_text_file();
        self::upload_to_dropbox();
    }
    
    private function start_process()
    {
        $page = 1; //orig value is 1
        $per_page = 50;
        while(true)
        {
            echo "\n page:$page";
            $url = $this->url["eol_collection"] . "&page=$page&per_page=$per_page";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $missing_iNat_photo_ids = self::get_missing_iNat_photo_ids();
                $collections = json_decode($json);
                $total_col = count($collections->collection_items);
                if($total_col == 0) break; //end of collections
                $k = 0;
                foreach($collections->collection_items as $col)
                {
                    $k++; //echo "\n page:$page | item $k of $total_col -- ";
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
                        if(!($h = Functions::file_open($this->dump_file, 'a'))) return;
                        fwrite($h, "http://eol.org/data_objects/".$rec['object_id'] . "\t" . $rec['photo_url'] . "\t" . $rec['observation_url'] . "\n");
                        fclose($h);
                    }
                }
            }
            $page++;
            // if($page > 1) break; //debug
        }//while
    }

    private function zip_text_file()
    {
        if(file_exists($this->dump_file))
        {
            $command_line = "gzip -c " . $this->dump_file . " >" . $this->dump_file . ".zip";
            $output = shell_exec($command_line);
            echo "\nfile zipped...\n";
        }
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
                if(!($h = Functions::file_open(DOC_ROOT . "/temp/iNat_photoID_not_found.txt", 'a'))) return;
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
        return $ids;
    }
    
    private function initialize_dump()
    {
        if(!($h = Functions::file_open($this->dump_file, 'w'))) return;
        fclose($h);
    }
    
    private function upload_to_dropbox()
    {
        require_library('connectors/DropboxAPI');
        $func = new DropboxAPI();
        $params['source']               = CONTENT_RESOURCE_LOCAL_PATH . 'iNat_EOL_object_urls.txt.zip';
        $params['dropbox_path']         = '/Public/iNaturalist/';
        $params['dropbox_access_token'] = '0L_P2JHHe60AAAAAAAARGn8Au3W0IEmAHbWgHQzSfyP_QMvomhOkuHc-ATnbb23Z';
        $func->upload_file_to_dropbox($params);
    }

}
?>