<?php
namespace php_active_record;

class PeerContentSynchronizer
{
    private $mysqli;
    private $peer_site_id;
    private $maximum_number_of_workers;
    const MAXIMUM_FAILED_ATTEMPTS = 10;
    const DEFAULT_MAXIMUM_NUMBER_OF_WORKERS = 8;
    
    function __construct($peer_site_id, $maximum_number_of_workers = self::DEFAULT_MAXIMUM_NUMBER_OF_WORKERS)
    {
        $this->mysqli = $GLOBALS['db_connection'];
        $this->peer_site_id = $peer_site_id;
        $this->maximum_number_of_workers = $maximum_number_of_workers;
        $this->number_of_running_workers = 0;
    }
    
    function initiate_master_thread()
    {
        $this->create_missing_download_statuses();
        
        // this looks for downloadable media present on some node in the network that are NOT either
        // successfully downloaded at this node; or tried and failed too many times
        foreach($this->mysqli->iterate_file("
            SELECT do.id data_object_id, do.object_cache_url, do.thumbnail_cache_url, do.peer_site_id, do.data_type_id, do.mime_type_id,
              ps.content_host_url_prefix, my_status.id download_status_id, my_status.failed_attempts, my_status.status_id
            FROM data_objects do
            JOIN peer_sites ps ON (do.peer_site_id=ps.id)
            JOIN data_objects_download_statuses master_status ON (do.id=master_status.data_object_id AND master_status.peer_site_id=do.peer_site_id
                AND master_status.peer_site_id!=$this->peer_site_id)
            JOIN data_objects_download_statuses my_status ON (do.id=my_status.data_object_id AND my_status.peer_site_id=$this->peer_site_id)
            WHERE do.data_type_id IN (". DataType::image()->id .", ". DataType::video()->id .", ". DataType::sound()->id .", ". DataType::flash()->id .", ". DataType::youtube()->id .")
            AND do.published = 1
            AND ((do.object_cache_url !='' AND do.object_cache_url IS NOT NULL) OR (do.thumbnail_cache_url !='' AND do.thumbnail_cache_url IS NOT NULL))
            AND my_status.status_id!=".Status::download_succeeded()->id."
            AND my_status.status_id!=".Status::download_in_progress()->id."
            AND my_status.failed_attempts < ". self::MAXIMUM_FAILED_ATTEMPTS ."
            AND master_status.status_id=".Status::download_succeeded()->id) as $row)
        {
            $params = array(
                'data_object_id' => $row[0],
                'object_cache_url' => $row[1],
                'thumbnail_cache_url' => $row[2],
                'source_peer_site_id' => $row[3],
                'data_type_id' => $row[4],
                'mime_type_id' => $row[5],
                'content_host_url_prefix' => $row[6],
                'data_objects_download_status_id' => $row[7],
                'failed_attempts' => $row[8],
                'status_id' => $row[9]);
            if($params['object_cache_url'] == 'NULL') $params['object_cache_url'] = null;
            if($params['thumbnail_cache_url'] == 'NULL') $params['thumbnail_cache_url'] = null;
            if(in_array($params['data_type_id'], array(6, 7))) continue;
            
            // $this->download_asset_from_peer($params);
            $this->queue_download($params);
        }
    }
    
    private function create_missing_download_statuses()
    {
        // Making sure there is at least a placeholder for every item needing to be downloaded
        $outfile = $this->mysqli->select_into_outfile("SELECT NULL, do.id, $this->peer_site_id, ". Status::download_pending()->id .", 0, NULL
            FROM data_objects do LEFT JOIN data_objects_download_statuses dods ON (do.id=dods.data_object_id AND dods.peer_site_id=$this->peer_site_id)
            WHERE do.data_type_id IN (". DataType::image()->id .", ". DataType::video()->id .", ". DataType::sound()->id .", ". DataType::flash()->id .", ". DataType::youtube()->id .")
            AND do.published = 1
            AND ((do.object_cache_url !='' AND do.object_cache_url IS NOT NULL) OR (do.thumbnail_cache_url !='' AND do.thumbnail_cache_url IS NOT NULL))
            AND dods.id IS NULL");
        $this->mysqli->load_data_infile($outfile, 'data_objects_download_statuses');
        unlink($outfile);
    }
    
    public function queue_download($params)
    {
        $count_processes = Functions::grep_processlist('sync_content_with_peers');
        $started_waiting = false;
        while($this->number_of_running_workers >= $this->maximum_number_of_workers)
        {
            if(!$started_waiting) echo "pool is full - waiting";
            echo ".";
            // wait .1 seconds before checking pool again
            usleep(100000);
            // reset the value of #OfRunningWorkers. Keeping this value cached will speed up the process.
            // when the number is lower than the #MaxWorkers, we can quickly spawn multiple processes and
            // don't have to check the processlist each time to see if we can add more - we know if we can add more
            // subtracting one here as the master process will get listed and we just want the worker processes
            $this->number_of_running_workers = (Functions::grep_processlist('sync_content_with_peers') - 1);
            $started_waiting = true;
        }
        if($started_waiting) echo "\n";
        
        static $i = 0;
        $i++;
        $script = DOC_ROOT . "rake_tasks/sync_content_with_peers.php ". escapeshellarg(serialize($params));
        echo "Downloading ($i) ID:". $params['data_object_id'] ." CacheURL:". $params['object_cache_url'] ."\n";
        shell_exec(PHP_BIN_PATH . "$script ENV_NAME=". $GLOBALS['ENV_NAME'] ." > /dev/null 2>/dev/null &");
        $this->number_of_running_workers++;
    }
    
    public function download_asset_from_peer($params)
    {
        $succeeded = null;
        switch($params['data_type_id'])
        {
            case DataType::image()->id:
                $succeeded = $this->download_from_peer($params, 'image');
                break;
            case DataType::video()->id:
            case DataType::youtube()->id:
            case DataType::flash()->id:
                if($params['object_cache_url'])
                {
                    $succeeded = $this->download_from_peer($params, 'video');
                }
                // download thumbnail
                if($params['thumbnail_cache_url'] && $succeeded !== false)
                {
                    $params['object_cache_url'] = $params['thumbnail_cache_url'];
                    $succeeded = $this->download_from_peer($params, 'image');
                }
                break;
            case DataType::sound()->id:
                if($params['object_cache_url'])
                {
                    $succeeded = $this->download_from_peer($params, 'audio');
                }
                // download thumbnail
                if($params['thumbnail_cache_url'] && $succeeded !== false)
                {
                    $params['object_cache_url'] = $params['thumbnail_cache_url'];
                    $succeeded = $this->download_from_peer($params, 'image');
                }
                break;
        }
        if($succeeded === true)
        {
            $this->mysqli->update("UPDATE data_objects_download_statuses SET status_id=". Status::download_succeeded()->id .", last_attempted = NOW()
                WHERE data_object_id=". $params['data_object_id'] ." AND peer_site_id=$this->peer_site_id");
        }elseif($succeeded === false)
        {
            $this->mysqli->update("UPDATE data_objects_download_statuses SET status_id=". Status::download_failed()->id .", failed_attempts=failed_attempts+1, last_attempted = NOW()
                WHERE data_object_id=". $params['data_object_id'] ." AND peer_site_id=$this->peer_site_id");
        }
    }
    
    private function download_from_peer($params, $data_type)
    {
        // convert 201011270221497 to 2010/11/27/02/21497
        $object_cache_path = self::object_cache_url_to_path($params['object_cache_url']);
        // create empty directories to store the content if we need to
        self::create_missing_directories($object_cache_path);
        
        if($data_type == 'image')
        {
            // also the original original? which we don't use...
            $suffixes_to_download = array(
                '_130_130.jpg', '_260_190.jpg',
                '_580_360.jpg', '_88_88.jpg',
                '_98_68.jpg', '_orig.jpg');
        }elseif($data_type == 'video')
        {
            $suffixes_to_download = array('.mov', '.mp4', '.flv', '.avi');
            $suffixes_to_download = self::promote_likely_extension($params['mime_type_id'], $suffixes_to_download);
        }elseif($data_type == 'audio')
        {
            $suffixes_to_download = array('.mp3', '.wav', '.ogg');
            $suffixes_to_download = self::promote_likely_extension($params['mime_type_id'], $suffixes_to_download);
        }
        
        foreach($suffixes_to_download as $suffix)
        {
            $object_url = $params['content_host_url_prefix'] . $object_cache_path . $suffix;
            $complete_local_path = CONTENT_LOCAL_PATH . $object_cache_path . $suffix;
            echo "Downloading ". $params['data_object_id'] ." ". $params['object_cache_url'] ." $object_url...\n";
            
            $succeeded = self::try_to_download($complete_local_path, $object_url, $data_type);
            if($data_type == 'image')
            {
                // with images, we need to make sure we have all different sizes we are looking for
                // if downloading any one of the assets fails, stop checking and set to download failed
                if(!$succeeded) return false;
            }else
            {
                // with other media we don't know the exact extension from the infromation in the DB
                // so we are trying different types to see what sticks. If downloading any one of
                // the files works then we can set to download succeeded
                if($succeeded) return true;
            }
        }
        
        // see the above comments - images should get here, other media should not
        if($data_type == 'image') return true;
        else return false;
    }
    
    private static function try_to_download($complete_local_path, $object_url, $object_type)
    {
        $succeeded = false;
        // first check existing local file
        if(self::file_exists_locally($complete_local_path, $object_url))
        {
            echo "THIS FILE ALREADY EXISTS!\n";
            $succeeded = true;
        }else
        {
            $content_manager = new ContentManager();
            $result = $content_manager->grab_file($object_url, 0, $object_type, NULL, NULL, $complete_local_path);
            if($result)
            {
                if(self::compare_checksums($complete_local_path, $object_url)) $succeeded = true;
            }
        }
        return $succeeded;
    }
    
    public static function promote_likely_extension($mime_type_id, $suffixes_to_try)
    {
        static $most_likely_extensions = array();
        if(@!$most_likely_extensions)
        {
            // sound
            $most_likely_extensions = array();
            $most_likely_extensions[MimeType::find_or_create_by_translated_label('audio/mpeg')->id] = '.mp3';
            $most_likely_extensions[MimeType::find_or_create_by_translated_label('audio/x-wav')->id] = '.wav';
            // video
            $most_likely_extensions[MimeType::find_or_create_by_translated_label('video/mp4')->id] = '.mp4';
            $most_likely_extensions[MimeType::find_or_create_by_translated_label('video/quicktime')->id] = '.mov';
            $most_likely_extensions[MimeType::find_or_create_by_translated_label('video/x-flv')->id] = '.flv';
        }
        if(isset($most_likely_extensions[$mime_type_id]))
        {
            array_unshift($suffixes_to_try, $most_likely_extensions[$mime_type_id]);
            return array_unique($suffixes_to_try);
        }
        return $suffixes_to_try;
    }
    
    
    private static function file_exists_locally($complete_local_path, $host_asset_url)
    {
        if(file_exists($complete_local_path))
        {
            if(!file_exists($complete_local_path . ".sha1")) ContentManager::create_checksum($complete_local_path);
            return self::compare_checksums($complete_local_path, $host_asset_url);
        }
        return false;
    }
    
    private static function compare_checksums($complete_local_path, $host_asset_url)
    {
        $source_sha1 = Functions::get_remote_file($host_asset_url . ".sha1");
        // if(!@$source_sha1 || $source_sha1 > 40)  $source_sha1 = sha1_file($host_asset_url);
        if(preg_match("/^([a-f0-9]{40}) /", $source_sha1, $arr)) $source_sha1 = $arr[1];
        $local_sha1 = Functions::get_remote_file($complete_local_path . ".sha1");
        if(strlen($source_sha1) == 40 && $source_sha1 == $local_sha1) return true;
        else echo "FAILING TO MATCH CHECKSUM $complete_local_path: $source_sha1 DOES NOT EQUAL $local_sha1\n";
        return false;
    }
    
    // convert 201011270221497 to 2010/11/27/02/21497
    private static function object_cache_url_to_path($object_cache_url)
    {
        if(strlen($object_cache_url) != 15) return null;
        return substr($object_cache_url, 0, 4) ."/". substr($object_cache_url, 4, 2)."/". substr($object_cache_url, 6, 2) ."/".
            substr($object_cache_url, 8, 2)."/". substr($object_cache_url, 10, 5);
    }
    
    private static function create_missing_directories($object_cache_path)
    {
        list($year, $month, $day, $hour) = explode("/", $object_cache_path);
        if(!file_exists(CONTENT_LOCAL_PATH . "$year")) mkdir(CONTENT_LOCAL_PATH . "$year");
        if(!file_exists(CONTENT_LOCAL_PATH . "$year/$month")) mkdir(CONTENT_LOCAL_PATH . "$year/$month");
        if(!file_exists(CONTENT_LOCAL_PATH . "$year/$month/$day")) mkdir(CONTENT_LOCAL_PATH . "$year/$month/$day");
        if(!file_exists(CONTENT_LOCAL_PATH . "$year/$month/$day/$hour")) mkdir(CONTENT_LOCAL_PATH . "$year/$month/$day/$hour");
    }
}

?>