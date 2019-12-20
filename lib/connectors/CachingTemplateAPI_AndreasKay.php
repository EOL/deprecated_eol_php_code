<?php
namespace php_active_record;
/* A template for caching calls*
This template is for DATA-1843: Andreas Kay resource
Future clients, can just copy this template and edit accordingly
*/
class CachingTemplateAPI_AndreasKay
{
    function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->download_options = array(
            'resource_id'        => $resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //should not expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->expire_seconds_specific = $this->download_options['expire_seconds'];

        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_php_cache/";
        else                           $this->download_options['cache_path'] = "/Volumes/Thunderbolt4/eol_cache/";      //used in Functions.php for all general cache
        $this->main_path = $this->download_options['cache_path'].$this->download_options['resource_id']."/";
        if(!is_dir($this->main_path)) mkdir($this->main_path);
        $this->api['GNRD'] = "https://gnrd.globalnames.org/name_finder.json?text=";
    }
    public function get_GNRD_output($tc_id, $pseudoBinomialsYN) //this is the function called remotely. $tc_id is the name string.
    {
        if($obj = self::retrieve_GNRD_output($tc_id)) {
            if(@$obj->names[0]->verbatim || @$obj->names[0]->scientificName) return $obj;
            else {
                echo "\ndito 100\n";
                if($pseudoBinomialsYN) { //write report for Katja. Names that are pseudo binimials but GNRD doesn't recognize it
                    $file = CONTENT_RESOURCE_LOCAL_PATH . "/reports/".$this->resource_id."_pseudo_binomials_not_in_GNRD.txt";
                    if(!($WRITE = Functions::file_open($file, "a"))) return;
                    fwrite($WRITE, $tc_id . "\n");
                    fclose($WRITE);
                }
            }
        }
        else exit("\nInvestigate: went here [$tc_id]\n");
    }
    private function retrieve_GNRD_output($tc_id)
    {
        /* No longer needed for Andreas Kay resource. But maybe needed for other resources who'll use this template.
        $filename = self::generate_path_filename($tc_id);
        if(file_exists($filename)) {
            if($GLOBALS['ENV_DEBUG']) echo "\nCache already exists. [$filename]\n";
            $file_age_in_seconds = time() - filemtime($filename);
            if($file_age_in_seconds < $this->expire_seconds_specific) return self::retrieve_json($filename); //not yet expired
            if($this->expire_seconds_specific === false)              return self::retrieve_json($filename); //doesn't expire

            if($GLOBALS['ENV_DEBUG']) echo "\nCache expired. Will run cypher now...\n";
            self::run_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
        else {
            if($GLOBALS['ENV_DEBUG']) echo "\nRun cypher query...\n";
            self::run_query($tc_id, $filename);
            return self::retrieve_json($filename);
        }
        */
        return self::run_query($tc_id);
    }
    private function retrieve_json($filename)
    {
        $json = file_get_contents($filename);
        return json_decode($json, true);
    }
    private function run_query($tc_id, $filename = NULL)
    {
        $saved = array();

        $url = $this->api['GNRD'].str_replace(' ', '+', $tc_id);
        echo "\naccessing [$url]\n";
        $json = Functions::lookup_with_cache($url, $this->download_options);
        $obj = json_decode($json);
        print_r($obj); //exit("\nstop 500\n");
        /*stdClass Object(
            [token_url] => https://gnrd.globalnames.org/name_finder.json?token=38aaa487ru
            [input_url] => 
            [file] => 
            [status] => 303
            [engine] => gnfinder
            [unique] => 
            [verbatim] => 1
            [parameters] => stdClass Object(
                    [return_content] => 
                    [with_verification] => 
                    [preferred_data_sources] => Array(
                        )
                    [detect_language] => 
                    [engine] => 0
                    [no_bayes] => 
                )
        )*/
        if($obj->status == 200) return $obj; //sometimes it goes here.
        elseif($obj->status == 303) {            //status 303 means you need to run 2nd token_url
            echo "\naccessing [$obj->token_url]\n";
            $json = Functions::lookup_with_cache($obj->token_url, $this->download_options);
            $obj2 = json_decode($json);
            print_r($obj2);
            if($obj2->status == 200) return $obj2;
            elseif($obj2->status == 303) {
                echo "\nstill 303\n";
                if($obj->token_url == $obj2->token_url) {
                    $options = $this->download_options;
                    $options['expire_seconds'] = 0;
                    $json = Functions::lookup_with_cache($obj->token_url, $options);
                    $obj3 = json_decode($json);
                    print_r($obj3);
                    if($obj3->status == 200) return $obj3;
                    elseif($obj3->status == 303) {
                        echo "\nstill 303 303\n";
                    }
                }
                exit;
            }
            
            /* No longer needed for Andreas Kay resource. But maybe needed for other resources who'll use this template.
            print_r($obj); exit;
            $WRITE = Functions::file_open($filename, "w");
            fwrite($WRITE, json_encode($obj)); fclose($WRITE);
            if($GLOBALS['ENV_DEBUG']) echo "\nSaved OK [$filename]\n";
            */
        }
        else return false;
    }
    private function generate_path_filename($tc_id)
    {
        $main_path = $this->main_path;
        $md5 = md5($tc_id);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($main_path . $cache1))           mkdir($main_path . $cache1);
        if(!file_exists($main_path . "$cache1/$cache2")) mkdir($main_path . "$cache1/$cache2");
        $filename = $main_path . "$cache1/$cache2/$tc_id.json";
        return $filename;
    }
}
?>