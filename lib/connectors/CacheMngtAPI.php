<?php
namespace php_active_record;
/* first client: iNatImagesAPI.php */
class CacheMngtAPI
{
    function __construct($cache_path)
    {
        $this->json_path = $cache_path;
    }
    /* ------------- START: retrieve module ------------- */
    function retrieve_json_obj($id, $object_format = true)
    {   $file = self::retrieve_path($id);
        if(is_file($file)) {
            $json = file_get_contents($file);
            if($object_format) return json_decode($json);       //object type
            else               return json_decode($json, true); //array type
        }
        return false;
    }
    function retrieve_path($md5)
    {   $filename = "$md5.json";
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        return $this->json_path . "$cache1/$cache2/$filename";
    }
    function save_json($id, $json)
    {   $file = self::build_path($id);
        if($f = Functions::file_open($file, "w")) {
            fwrite($f, $json);
            fclose($f);
        }
        else exit("\nCannot write file\n");
    }
    function build_path($md5)
    {
        $filename = "$md5.json";
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($this->json_path . $cache1)) mkdir($this->json_path . $cache1);
        if(!file_exists($this->json_path . "$cache1/$cache2")) mkdir($this->json_path . "$cache1/$cache2");
        return $this->json_path . "$cache1/$cache2/$filename";
    }
    /* ------------- END: retrieve module ------------- */
}