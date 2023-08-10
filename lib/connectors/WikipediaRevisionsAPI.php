<?php
namespace php_active_record;
/* connector: [polytraits_new.php]
*/
class WikipediaRevisionsAPI
{
    function __construct($params)
    {
        $this->params = $params;
        $this->download_options = array('cache' => 1, 'resource_id' => 'wikipedia_revisions', 'expire_seconds' => 60*60*24*10, //10 days cache
        'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
        // $this->download_options['expire_seconds'] = false;
        if(Functions::is_production()) $this->download_options['download_wait_time'] = 1000000; //4 secs.
        $this->debug = array();
        $this->api['endPoint'] = "https://".$this->params['language'].".wikipedia.org/w/api.php"; //"https://en.wikipedia.org/w/api.php";

        $this->cache_path = DOC_ROOT . $GLOBALS['MAIN_CACHE_PATH'] . 'wikipedia_revisions/';
    }

    function get_page_revision_history($title, $language)
    {
        if($arr = self::retrieve_cache($title, $language)) return $arr;
        else return false;
    }
    private function retrieve_cache($title, $language)
    {
        $filename = self::get_filename($title, $language);
        if(file_exists($filename)) {
            $json = file_get_contents($filename);
            $arr = json_decode($json, true);
            if($arr) return $arr;
        }
    }
    private function get_filename($title, $language)
    {
        $main_path = $this->cache_path;
        $md5 = md5($title.$language);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$md5.json";
        return $filename;
    }
    function get_page_latest_revision($title, $language)
    {
        if($arr = self::run_revision_query($title, $language)) return $arr;
        else {
            echo "\nERROR: Should not go here.\ntitle and language don't match: [$title] [$language]\n\n";
            return false;
        }
    }
    private function run_revision_query($title, $language)
    {
        $i = 0; $str = "";
        $params = [
            "action" => "query",
            "prop" => "revisions",
            "titles" => $title, //"Ocean sunfish", //"Mola mola",
            "rvlimit" => "1", //"5",
            "rvslots" => "main",
            "formatversion" => "2",
            "format" => "json",
        ];
        foreach($params as $key => $value) { $i++;
            if($i == 1) $str .= "?$key=$value";
            else        $str .= "&$key=$value";
        }
        $url = $this->api['endPoint'].$str;
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*10; // 10 days expire
        if($json = Functions::lookup_with_cache($url, $options)) {
            $arr = json_decode($json, true); // print_r($arr);
            $arr = @$arr['query']['pages'][0]['revisions'][0];
            if(!$arr) return false;
            return $arr;
        }
    }
    function save_to_history($arr, $title, $language)
    {
        $latest_rev_json = json_encode($arr);
        $filename = self::get_filename($title, $language);
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, $latest_rev_json . "\n");
        fclose($WRITE);
    }
}
?>