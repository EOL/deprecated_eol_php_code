<?php
namespace php_active_record;
/* connector: [eol_harvest_publish.php]
*/
class EOLHarvestPublishAPI
{
    function __construct()
    {
        $this->download_options = array('cache' => 1, 'resource_id' => 'EOLHarPub', 'expire_seconds' => 60*60*1, 
        'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 2); //1 hr to expire
        $this->resource_harvest_list = "http://content.eol.org/?page=PAGE_NUM&per_page=PER_PAGE";
    }
    function generate_cache_for_EOLResourcesHarvestList()
    {
        $resource_ids_names_list = self::get_resource_ids_and_names();
        print_r($resource_ids_names_list);
        self::generate_web_page($resource_ids_names_list);
    }
    private function get_resource_ids_and_names()
    {
        $final = array();
        $page_num = 0; $per_page = 15;
        while(true) { $page_num++;
            $url = $this->resource_harvest_list;
            $url = str_replace("PAGE_NUM", $page_num, $url);
            $url = str_replace("PER_PAGE", $per_page, $url);
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                // <div class='header'><a href="/resources/1035">Zieger and Meyer Rochow 2008</a></div>
                if(preg_match_all("/<div class=\'header\'><a href=\"\/resources\/(.*?)<\/a>/ims", $html, $arr)) {
                    // print_r($arr[1]); exit("\nditox 1\n");
                    /*Array(
                        [0] => 550">000_English Vernaculars for Landmark Taxa
                        [1] => 763">3i database: Typhlocybinae
                        [2] => 1083">3I: Cicadellinae
                        [3] => 1084">3I: Deltocephalinae
                        [4] => 1196">Achatz et al 2013
                        [5] => 598">Addisonia v1
                        [6] => 599">Addisonia volume 2
                        [7] => 600">Addisonia volume 3
                        [8] => 419">Adriatic Sea Species List
                        [9] => 497">Aegean Sea Species List
                        [10] => 150">Afghanistan Species List
                        [11] => 734">Africa Tree Database
                        [12] => 728">African Amphibians
                        [13] => 597">African Flora
                        [14] => 707">Afrotropical Birds LifeDesk
                    )*/
                    $tmp_arr = $arr[1];
                    foreach($tmp_arr as $str) {
                        $id = false; $name = false;
                        if(preg_match("/elicha(.*?)\"/ims", "elicha".$str, $arr)) $id = $arr[1];
                        if(preg_match("/\>(.*?)elicha/ims", $str."elicha", $arr)) $name = $arr[1];
                        if($id && $name) $final[$id] = $name;
                    }
                }
                else break;
            }
            else exit("\nERROR: Not accessible [$url].\n");
            // if($page_num >= 3) break; //debug only
        }
        return $final;
    }
    private function generate_web_page($resource_ids_names_list)
    {
        $file = CONTENT_RESOURCE_LOCAL_PATH . "reports/EOL_harvest_list.html";
        $WRITE = Functions::file_open($file, "w");
        fwrite($WRITE, "EOL Harvested Resources &nbsp;&nbsp; n = ".count($resource_ids_names_list)."<br>\n");
        $i = 0;
        foreach($resource_ids_names_list as $id => $name) { $i++;
            // http://content.eol.org/resources/626
            $num = self::format_number_with_leading_zeros($i, 5);
            $row = "$num. <a target='$id $name' href='http://content.eol.org/resources/$id'>$name</a><br>";
            fwrite($WRITE, $row."\n");
        }
        fclose($WRITE);
    }
    private function format_number_with_leading_zeros($num, $padding)
    {
        return str_pad($num, $padding, "_", STR_PAD_LEFT);
    }

    /* not used
    private function get_all_in_between_inclusive($left, $right, $html)
    {
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            return $arr[1];
        }
    }
    */
}
?>