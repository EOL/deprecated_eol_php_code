<?php
namespace php_active_record;
/* connector during dev: [wikipedia_revision.php] 
   normal operation: is called from WikipediaAPI

   it is a lib. that detects if a page (will expire) (does not expire) (ignored)

*/
class WikipediaRevisionsAPI
{
    function __construct($params)
    {
        $this->download_options = array('cache' => 1, 'resource_id' => 'wikipedia_revisions', 'expire_seconds' => 60*60*24*10, //10 days cache
        'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
        // $this->download_options['expire_seconds'] = false;
        if(Functions::is_production()) $this->download_options['download_wait_time'] = 500000; //half a sec.
        $this->api['endPoint'] = "https://".$params['language'].".wikipedia.org/w/api.php"; //"https://en.wikipedia.org/w/api.php";
        $this->cache_path = DOC_ROOT . $GLOBALS['MAIN_CACHE_PATH'] . 'wikipedia_revisions/';
        if(!is_dir($this->cache_path)) mkdir($this->cache_path);
    }
    function wikipedia_revision($params)
    {   /* check if revision history already exists:
            if not: create rev history ---> proceed with downloading the page; expires now
            if yes: get the revision history record
                compare the old and new timestamp:
                    if timestamps are equal     ---> set $options['expire_seconds'] = false;
                    if timestamps are not equal ---> set $options['expire_seconds] = 0; */
        if($rev_history = self::get_page_revision_history($params['title'], $params['language'])) {
            debug("\nHas page revision history already.\n");
            if($rev_latest = self::get_page_latest_revision($params['title'], $params['language'])) {
                if($GLOBALS['ENV_DEBUG']) {
                    echo "\nrev_history"; print_r($rev_history);
                    echo "\nrev_latest"; print_r($rev_latest);    
                }
                $history_last_edited = $rev_history['timestamp'];
                $latest_last_edited = $rev_latest['timestamp'];
                if($history_last_edited == $latest_last_edited) $expire_seconds = false; //does not expire
                else {
                                                                $expire_seconds = 0;     //expires now
                                                                debug("\nDifferent timestamp.");
                }
            }
            else {
                echo "\nNo wikipedia page for this title and language**.\n"; //Does not go here actually.
                $expire_seconds = "do not proceed";
            }
        }
        else { //revision history not found; create one
            debug("\nNo page revision history yet.\n");
            if($rev_initial = self::get_page_latest_revision($params['title'], $params['language'])) {
                self::save_to_history($rev_initial, $params['title'], $params['language']);
                if($GLOBALS['ENV_DEBUG']) { echo "\nInitial rev history saved."; print_r($rev_initial); }
                $expire_seconds = 0; //expires now    
            }
            else {
                echo "\nNo wikipedia page for this title and language [".$params['title']."][".$params['language']."].\n"; //maybe doesn't go here as well.
                $expire_seconds = "do not proceed";
            }
        }
        if($expire_seconds === 0)                   debug("\nExpires now.");
        elseif($expire_seconds === false)           debug("\nSame timestamp, does not expire.");
        elseif($expire_seconds == "do not proceed") debug("\nWikipedia not found.");
        else exit("\nInvestigate: this case is not captured.\n");
        return $expire_seconds;
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
        if($arr = self::run_revision_query($title, $language)) { // print_r($arr); exit;
            return $arr;
        }
        else {
            echo "\n[title] and [language] don't match: [$title] [$language]\n\n";
            return false;
        }
    }
    private function run_revision_query($title, $language)
    {
        $i = 0; $str = "";
        $parameters = [
            "action" => "query",
            "prop" => "revisions",
            "titles" => $title, //"Ocean sunfish", //"Mola mola",
            "rvlimit" => "1", //"5",
            "rvslots" => "main",
            "formatversion" => "2",
            "format" => "json",
        ];
        foreach($parameters as $key => $value) { $i++;
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