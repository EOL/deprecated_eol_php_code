<?php
namespace php_active_record;
/* 
connector: also ran from: html2mediawiki_eoearth.php
This generates the HTML files for:
http://editors.eol.org/eoearth/wiki/Search_Results_for_Main_Topics
*/

class EoEarthAPI
{
    function __construct()
    {
        $this->search_url = "http://www.eoearth.org/results/all/?group=6515&all=1&onlyMyBooks=&store=0&narrow=199749&trustedOnly=1";
        $this->download_options = array("expire_seconds" => false, "download_wait_time" => 5000000, "timeout" => 3600, "delay_in_minutes" => 1, 'cache_path' => '/Volumes/Eli black/eoearth_cache/');
        $this->html_dir = DOC_ROOT . '/public/tmp/eoe/html/';
    }

    function start()
    {
        $topics = array("About the EoE", "Agricultural & Resource Economics", "Biodiversity", "Biology", "Climate Change", "Ecology", "Environmental & Earth Science", "Energy", "Environmental Law & Policy", 
        "Environmental Humanities", "Food", "Forests", "Geography", "Hazards & Disasters", "Health", "Mining & Materials", "People", "Physics & Chemistry", "Pollution", "Society & Environment", "Water", 
        "Weather & Climate", "Wildlife");
        
        // $topics = array("Biodiversity"); //debug
        
        foreach($topics as $topic)
        {
            $this->count = array(); //it initializes every topic
            if(($OUT = Functions::file_open($this->html_dir . str_replace(" ", "_", $topic) . ".html", "w"))) {}
            else exit("\nFile access problem.\n");
            
            $url = $this->search_url . "&q=$topic";
            if($html = Functions::lookup_with_cache($url, $this->download_options))
            {
                if(preg_match("/page 1 of (.*?)<\/title>/ims", $html, $arr))
                {
                    $count = $arr[1];
                    for($i=1; $i<=$count; $i++)
                    {
                        if($html = Functions::lookup_with_cache($url."&page=$i", $this->download_options))
                        {
                            if(preg_match_all("/<h1>(.*?)<\/h1>/ims", $html, $arr))
                            {
                                print_r($arr[1]);
                                foreach($arr[1] as $t)
                                {
                                    if(preg_match("/>(.*?)<\/a>/ims", $t, $arr2))
                                    {
                                        $new_link_text = $arr2[1];
                                        $word_count = str_word_count($new_link_text);
                                        if($word_count < 3) $new_link_text .= " ($topic)";
                                        //--------------
                                        @$this->count[$new_link_text]++;
                                        /* previous 
                                        if($word_count < 3) $c = ($this->count[$new_link_text] > 1 ? $this->count[$new_link_text] : ''); //ternary
                                        else                $c = "";
                                        */
                                        $c = ($this->count[$new_link_text] > 1 ? $this->count[$new_link_text] : ''); //ternary
                                        //--------------
                                        $t = str_replace($arr2[1], $new_link_text. " $c", $t);
                                    }
                                    fwrite($OUT, $t."<br>");
                                }
                                // break; //debug
                            }
                        }
                    }
                }
            }
            fclose($OUT);
        }
    }
}
?>