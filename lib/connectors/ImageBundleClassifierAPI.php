<?php
namespace php_active_record;
/* connector: [image_bundle_classifier.php] - DATA-1865

For 3-c -- works OK
e.g.
https://www.flickr.com/photos/biodivlibrary/sets/72157628019337516/
then view source get:
e.g.
https://live.staticflickr.com/6222/6298833546_5a7eb31c73.jpg

*/
class ImageBundleClassifierAPI
{
    function __construct()
    {
        $this->debug = array();
        $this->download_options = array(
            'resource_id'        => 'Katie',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //expires in a month
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5, 'cache' => 1);
        
        $this->pages['taxa_per_resource'] = 'https://eol.org/resources/RESOURCE_ID/nodes';
        $this->pages['media_per_taxon_per_resource'] = 'https://eol.org/pages/PAGE_ID/media?resource_id=RESOURCE_ID';
        
        if(Functions::is_production()) {
            $this->path['destination'] = '/extra/other_files/bundle_images/classifier/';
            $this->prefix = 'https://editors.eol.org/other_files/';
        }
        else {
            $this->path['destination'] = '/Volumes/AKiTiO4/other_files/bundle_images/classifier/';
            $this->prefix = 'http://localhost/other_files/';
        }
        if(!is_dir($this->path['destination'])) mkdir($this->path['destination']);
    }
    function task1_Herbarium_Sheets()
    {   
        /* works OK
        $page_max = self::get_page_range('x', 'https://eol.org/pages/71348/media?resource_id=410', 'pages');
        exit("\n[$page_max]\n");
        */
        /* 
        NMNH Botany in DwCA (420)
        https://eol.org/resources/420
        https://eol.org/resources/420/nodes - list of species
        https://eol.org/resources/420/nodes?page=1
        https://eol.org/pages/71348/media?resource_id=420 - list of media per species per resource
        https://eol.org/pages/71348/media?resource_id=410
        */
        if($FILE = Functions::file_open($this->path['destination'].$report.'.txt', 'w')) fclose($FILE); //initialize report
        $resource_id = 420;
        $page_max = self::get_page_range($resource_id);
        self::loop_taxa_pages_per_resource($page_max, $resource_id);
    }
    private function loop_taxa_pages_per_resource($page_max, $resource_id)
    {
        $page_ids = self::get_all_page_ids($page_max, $resource_id);
        foreach($page_ids as $page_id) {
            self::get_images_per_page_id_per_resource($page_id, $resource_id);
        }
    }
    private function get_images_per_page_id_per_resource($page_id, $resource_id)
    {
        $url = str_replace('PAGE_ID', $page_id, $this->pages['media_per_taxon_per_resource']);
        $url = str_replace('RESOURCE_ID', $resource_id, $url);
        // $url = 'https://eol.org/pages/71348/media?resource_id=410'; //debug only
        echo "\nprocessing page: [$page_id]\n";
        $page_max = self::get_page_range('x', $url, 'pages');
        if(!$page_max) $page_max = 1;
        // exit("\n[$page_max]\n");
        self::loop_media_pages_per_taxon_per_resource($page_id, $resource_id, $page_max);
    }
    private function loop_media_pages_per_taxon_per_resource($page_id, $resource_id, $page_max)
    {
        $url = str_replace('PAGE_ID', $page_id, $this->pages['media_per_taxon_per_resource']);
        $url = str_replace('RESOURCE_ID', $resource_id, $url);
        $url .= "&page=";
        for($i = 1; $i <= $page_max; $i++) {
            echo "\n$i of $page_max $url".$i;
            /*
            <div class='lightbox-img-contain uk-flex'>
            <img alt="Image of manioc hibiscus" src="https://content.eol.org/data/media/71/20/dc/519.10615598.jpg" />
            </div>
            <div class='lightbox-overlay lightbox-overlay-top uk-position-top uk-hidden-hover'>
            <ul class='uk-iconnav uk-text-center uk-inline lightbox-icons'>
            <li class='uk-inline'>
            <a alt="make exemplar" rel="nofollow" data-method="post" href="/page_icons?medium_id=8447919&amp;page_id=71348"><i class='trophy icon'></i></a>
            </li>
            <li class='uk-inline'>
            <a alt="add to collection" href="/collected_pages/new?medium_ids%5B%5D=8447919&amp;page_id=71348"><i class='plus icon'></i></a>
            </li>
            </ul>
            <a class='uk-modal-close-default uk-close-large close-btn' href='' uk-close></a>
            <div class='lightbox-overlay-content-top'>
            <div>
            <a href="/media/8447919">01222657</a>
            <div class='ui label'>cc-by-nc-sa-3.0</div>
            </div>
            <div>
            <i class='copyright icon'></i>
            */
            if($html = Functions::lookup_with_cache($url.$i, $this->download_options)) {
                if(preg_match_all("/<div class='lightbox-img-contain uk-flex'>(.*?)<i class='copyright icon'>/ims", $html, $arr)) {
                    echo "\nImages: ".count($arr[1])."\n";
                    foreach($arr[1] as $html2) {
                        $ret = array();
                        if(preg_match("/src=\"(.*?)\"/ims", $html2, $arr2)) $ret['media_url'] = $arr2[1];
                        if(preg_match("/\"\/media\/(.*?)\">/ims", $html2, $arr2)) $ret['object_id'] = $arr2[1];
                        if(preg_match("/<div class='ui label'>(.*?)<\/div>/ims", $html2, $arr2)) $ret['license'] = $arr2[1];
                        if($ret['media_url'] && $ret['object_id'] && $ret['license']) {
                            print_r($ret);
                            self::write_report($ret, 'herbarium_sheets');
                        }
                        else exit("\ninvestigate [$page_id] [$resource_id]\n");
                    }
                }
            }
        }
    }
    private function get_all_page_ids($page_max, $resource_id)
    {
        $page_ids = array();
        $url = str_replace('RESOURCE_ID', $resource_id, $this->pages['taxa_per_resource'])."?page=";
        for($i = 1; $i <= $page_max; $i++) {
            echo "\n$i of $page_max ";
            if($html = Functions::lookup_with_cache($url.$i, $this->download_options)) {
                /* <a href="/pages/49745334">× <i>Chiranthofremontia lenzii</i> Dorr</a> */
                if(preg_match_all("/<a href=\"\/pages\/(.*?)\">/ims", $html, $arr)) {
                    $page_ids = array_merge($page_ids, $arr[1]);
                }
            }
            if($i >= 60) break; //debug only
        }
        echo "\nTaxa pages total: ".count($page_ids)."\n";
        return $page_ids;
    }
    private function get_page_range($resource_id, $sought_url = false, $type = 'resources')
    {
        if($sought_url) $url = $sought_url;
        else            $url = str_replace('RESOURCE_ID', $resource_id, $this->pages['taxa_per_resource']);
        /* option 1 -> resources
        <li class='last'>
        <a href="/resources/420/nodes?page=1002">Last &raquo;</a>
        </li>
        */
        /* option 2 -> pages
        <li class='last'>
        <a href="/pages/71348/media?page=11&amp;resource_id=410">Last &raquo;</a>
        </li>
        */
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match("/<li class='last'>(.*?)<\/li>/ims", $html, $arr)) {
                $html2 = $arr[1];
                if($type == 'resources') {
                    if(preg_match("/page=(.*?)\">Last /ims", $html2, $arr)) {
                        $page_max = $arr[1];
                        return $page_max;
                    }
                }
                elseif($type == 'pages') {
                    if(preg_match("/page=(.*?)\&amp\;/ims", $html2, $arr)) {
                        $page_max = $arr[1];
                        return $page_max;
                    }
                }
                else exit("\nundefined type\n");
            }
        }
    }
    private function write_report($ret, $report)
    {
        /*Array(
            [media_url] => https://content.eol.org/data/media/70/d8/0c/519.10300438.jpg
            [object_id] => 8450393
            [license] => cc-by-nc-sa-3.0
        )*/
        
        @$this->total_write++;
        if($FILE = Functions::file_open($this->path['destination'].$report.'.txt', 'a')) {
            fwrite($FILE, implode("\t", $ret)."\n");
            fclose($FILE);
        }
        if($this->total_write >= 3000) exit("\n3000 saved rows. Will stop process.\n");
    }
}
?>