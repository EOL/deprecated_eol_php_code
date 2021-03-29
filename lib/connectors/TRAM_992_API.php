<?php
namespace php_active_record;
/* */
class TRAM_992_API
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'opendata', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->opendata_api['tag taxonomic inference'] = 'https://opendata.eol.org/api/3/action/package_search?q=taxonomic+inference&start=0&rows=200&&sort=metadata_modified+desc';
    }
    function start()
    {
        if($json = Functions::lookup_with_cache($this->opendata_api['tag taxonomic inference'], $this->download_options)) {
            $obj = json_decode($json); //print_r($obj);
            $i = 0;
            foreach($obj->result->results as $rec) {
                // print_r($rec->tags); exit;
                if(@$rec->tags{0}->name == 'taxonomic inference') {
                    self::process_rec($rec);
                    $i++;
                }
            }
            echo "\nResources: [$i]\n";
        }
    }
    private function process_rec($rec)
    {   //print_r($rec);
        if(count($rec->resources) > 1) { print_r($rec); exit("\nMore than one resources?\n"); }
        foreach($rec->resources as $resource) self::process_resource($resource);
    }
    private function process_resource($res)
    {   // print_r($res);
        /*stdClass Object(
            [description] => 
            [name] => Lewis and Taylor, 1965
            [format] => ZIP
            [url] => https://opendata.eol.org/dataset/10c26a35-e332-4c56-94fd-a5b39d245ff6/resource/98edf631-a461-4761-a25e-f36c6527dc46/download/archive.zip
            [id] => 98edf631-a461-4761-a25e-f36c6527dc46
        )*/
        exit("\n-exit muna-\n");
    }
}
?>