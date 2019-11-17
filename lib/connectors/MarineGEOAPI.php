<?php
namespace php_active_record;
/* connector: [marine_geo.php] https://eol-jira.bibalex.org/browse/COLLAB-1004 */
class MarineGEOAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only
        
        $this->api['coll_num'] = 'http://www.boldsystems.org/index.php/API_Public/specimen?ids=COLL_NUM&format=json';
        
    }
    function start()
    {
        $coll_num = 'KB17-277';
        self::search_collector_no($coll_num);
    }
    private function search_collector_no($coll_num)
    {
        $url = str_replace('COLL_NUM', $coll_num, $this->api['coll_num']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json, true);
            print_r($arr);
        }
    }
}
?>
