<?php
namespace php_active_record;
/* connector: [inat] DATA-1594
Connector loops to the resource collections using the API and compiles all the object page URLs (using the object API), 
the source media URLs and the iNat observation URLs.
http://eol.org/api/collections/1.0/9528.json?filter=images&sort_by=recently_added&sort_field=&cache_ttl=
*/

Just FYI.
The parameter 'page' in Collections API is not working.
Attention: Jeremy Rice. page=1 and page=2 is giving the same results:
page 1: https://eol.org/api/collections/1.0?id=9528&page=1&per_page=50&filter=&sort_by=recently_added&sort_field=&cache_ttl=&language=en&format=json
page 2: https://eol.org/api/collections/1.0?id=9528&page=2&per_page=50&filter=&sort_by=recently_added&sort_field=&cache_ttl=&language=en&format=json

But I can scrape the HTML as second option. Which I think I did on another resource because of the same problem.
http://eol.org/collections/9528/images?page=1&sort_by=1&view_as=3
http://eol.org/collections/9528/images?page=2&sort_by=1&view_as=3

Will proceed with 2nd option for now.


class CollectionsAPI
{
    function __construct($collection_id)
    {
        $this->url["eol_collection"] = "https://eol.org/api/collections/1.0/".$collection_id.".json?filter=images&sort_by=recently_added&sort_field=&cache_ttl=";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/";
        $this->download_options = array("download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1, "delay_in_minutes" => 1);
        $this->download_options['expire_seconds'] = false; //always false, will not change anymore...
        $this->dump_file = CONTENT_RESOURCE_LOCAL_PATH . $collection_id."_EOL_object_urls.txt";
        
        if(Functions::is_production()) {
            $this->download_options['cache_path'] = "/extra/eol_cache_collections/";
        }
        else {
            $this->download_options['cache_path'] = "/Volumes/AKiTiO4/eol_cache_collections/";
        }
    }

    function generate_link_backs()
    {
        self::initialize_dump();
        self::start_process();
        self::zip_text_file();
        // self::upload_to_dropbox();
    }
    private function get_total_pages()
    {
        $page = 1; //orig value is 1
        $per_page = 50;
        $url = $this->url["eol_collection"] . "&page=$page&per_page=$per_page";
    }
    private function start_process()
    {
        $total_pages = self::get_total_pages();
        $page = 1; //orig value is 1
        $per_page = 50;
        while(true)
        {
            echo "\n page:$page";
            $url = $this->url["eol_collection"] . "&page=$page&per_page=$per_page";
            echo "\n[$url]\n"; exit;
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $missing_iNat_photo_ids = self::get_missing_iNat_photo_ids();
                $collections = json_decode($json);
                $total_col = count($collections->collection_items);
                if($total_col == 0) break; //end of collections
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
                            print_r($object);
                            $rec["photo_url"] = @$object->dataObjects[0]->source; //iNat photo page url
                            
                            /* check if photo id is inaccessible */
                            // $photo_id = self::get_iNat_photo_id($rec["photo_url"]);
                            // if(in_array($photo_id, $missing_iNat_photo_ids)) continue;
                            
                        }
                    }
                    if($rec)
                    {
                        if(!($h = Functions::file_open($this->dump_file, 'a'))) return;
                        fwrite($h, "http://eol.org/data_objects/".$rec['object_id'] . "\t" . $rec['photo_url'] . "\t" . "\n");
                        fclose($h);
                    }
                }
            }
            $page++;
            if($page > 1) break; //debug
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