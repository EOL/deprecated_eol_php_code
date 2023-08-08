<?php
namespace php_active_record;
/* connector: [polytraits_new.php]
*/
class WikipediaRevisionsAPI
{
    function __construct($folder = false)
    {
        $this->download_options = array('cache' => 1, 'resource_id' => 'wikipedia_revisions', 'expire_seconds' => 60*60*24*10, //10 days cache
        'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1);
        // $this->download_options['expire_seconds'] = false;
        if(Functions::is_production()) $this->download_options['download_wait_time'] = 1000000; //4 secs.
        $this->debug = array();

    }
    function initialize()
    {   
    }
    private function main()
    {   
        // if($html = Functions::lookup_with_cache($url, $this->download_options)) { // for taxa page lists...
    }
  
}
?>
