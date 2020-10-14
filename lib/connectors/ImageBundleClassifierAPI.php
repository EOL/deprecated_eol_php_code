<?php
namespace php_active_record;
/* connector: [image_bundle_classifier.php] - DATA-1865

For 3-c -- works OK
e.g.
https://www.flickr.com/photos/biodivlibrary/sets/72157628019337516/
then view source get:
e.g.
https://live.staticflickr.com/6222/6298833546_5a7eb31c73.jpg

https://eol.org/resources/416   NMNH Birds in DwC A 
https://eol.org/resources/463   NMNH Entomology (nmnh_entomology_)
https://eol.org/resources/638   NMNH Marine Dinoflagellates (nmnh_marine_dino)
https://eol.org/resources/42    NMNH Primate Measurements   (NMNH primates)
https://eol.org/resources/462   NMNH Mammals    (nmnh_mammals_mam)
https://eol.org/resources/464   NMNH Invertebrate Zoology   (nmnh_invertebrat)
https://eol.org/resources/417   NMNH Fishes (nmnh_fishes_in_d)
https://eol.org/resources/464   NMNH Invertebrate Zoology   (nmnh_invertebrat)
https://eol.org/resources/462   NMNH Mammals    (nmnh_mammals_mam)
https://eol.org/resources/415    NMNH Entomology in DwC A   (nmnh_entomology_)
https://eol.org/resources/419   NMNH Mammals in DwC A   (nmnh_mammals)
https://eol.org/resources/414   NMNH Invertebrate Zoology in DwC A  (NMNH IZ)

*/
class ImageBundleClassifierAPI
{
    function __construct()
    {
        $this->debug = array();
        $this->download_options = array(
            'resource_id'        => 'Katie',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*1, //expires in a day
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
        /*
        https://editors.eol.org/other_files/bundle_images/classifier/herbarium_sheets.txt
        */
        $this->task_3a_DwCA['NMNH Mammals'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/344.tar.gz';
        $this->task_3a_DwCA['NMNH Fishes'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/342.tar.gz';
        $this->task_3a_DwCA['NMNH Entomology'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/176.tar.gz';
    }
    function print_totals()
    {
        echo "\nAs of ".date("d M Y")."\n";
        Functions::show_totals($this->path['destination'].'herbarium_sheets'.'.txt');
        Functions::show_totals($this->path['destination'].'herbarium_sheets'.'_download.txt');
        Functions::show_totals($this->path['destination'].'Zoological_illustrations'.'.txt');
        Functions::show_totals($this->path['destination'].'Zoological_illustrations'.'_download.txt');
        Functions::show_totals($this->path['destination'].'Botanical_illustrations'.'.txt');
        Functions::show_totals($this->path['destination'].'Botanical_illustrations'.'_download.txt');
        Functions::show_totals($this->path['destination'].'maps'.'.txt');
        Functions::show_totals($this->path['destination'].'Phylogeny_images'.'.txt');
        echo "\n-end-\n";
    }
    function task_2_Maps($report)
    {
        $media_urls = array();
        if($report == 'maps') {
            $options['url'] = "https://commons.wikimedia.org/w/index.php?title=Special:Search&limit=500&offset=0&ns0=1&ns6=1&ns12=1&ns14=1&ns100=1&ns106=1&search=%22map%22&advancedSearch-current=%7B%7D";
            $options['limit'] = 3000;
        }
        elseif($report == 'Phylogeny_images')
        {
            $options['url'] = "https://commons.wikimedia.org/w/index.php?title=Special:Search&limit=500&offset=0&ns0=1&ns6=1&ns12=1&ns14=1&ns100=1&ns106=1&search=%22phylogenetic+tree%22&advancedSearch-current=%7b%7d";
            $options['limit'] = 3000; //1000;
        }
        $url = $options['url'];
        while(true) {
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                $url = false;
                /*
                <img alt="" src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Turgot_map_of_Paris%2C_sheet_2_-_Norman_B._Leventhal_Map_Center.jpg/120px-Turgot_map_of_Paris%2C_sheet_2_-_Norman_B._Leventhal_Map_Center.jpg" decoding="async" width="120" height="74" data-file-width="8950" data-file-height="5540" />
                */
                $urls = self::get_media_urls_from_page($html);
                
                foreach($urls as $u) $media_urls[$u] = '';
                echo "\nCounts: ".count($media_urls)."\n";
                if(count($media_urls) > $options['limit']) break;
                
                if($url = self::has_next500_link($html)) {}
                else break;
            }
            else break;
        }
        
        //write the report
        if($FILE = Functions::file_open($this->path['destination'].$report.'.txt', 'w')) {}
        foreach($media_urls as $url => $val) fwrite($FILE, $url."\n");
        fclose($FILE);
    }
    private function get_media_urls_from_page($html)
    {
        $final = array();
        if(preg_match_all("/<img alt=\"\" src=\"https\:\/\/upload.wikimedia.org\/wikipedia\/commons\/thumb\/(.*?)\"/ims", $html, $arr)) {
            $parts = $arr[1];
            foreach($parts as $part) {
                $url = "https://upload.wikimedia.org/wikipedia/commons/thumb/".$part;
                $num_px = self::get_num_px($url); //e.g. f/page1-70px-The_American_na -> 70px
                $url = str_replace($num_px, "800px", $url);
                $final[] = $url;
            }
        }
        return $final;
    }
    private function get_num_px($str)
    {
        // $str = "oya%29.pdf/page1-72px-";
        $px_pos = strpos($str, "px");
        echo "\n[$px_pos]\n";
        while(true) {
            $px_pos--;
            $char = substr($str, $px_pos, 1);
            if(is_numeric($char)) $chars[] = $char;
            else break;
        }
        $chars = array_reverse($chars);
        $num = implode("", $chars);
        return $num."px";
    }
    private function has_next500_link($html)
    {
        // https://commons.wikimedia.org/w/index.php
        // previous 500 | <a href="/w/index.php?title=Special:Search&amp;limit=500&amp;offset=500&amp;profile=default&amp;search=%22map%22" title="Next 500 results"
        // previous 500</a> | <a href="/w/index.php?title=Special:Search&amp;limit=500&amp;offset=1000&amp;profile=default&amp;search=%22map%22" title="Next 500 results"

        if(preg_match("/previous 500 \| \<a href\=\"(.*?)\" title\=\"Next 500 results\"/ims", $html, $arr)) {
            $part = str_replace("&amp;", "&", $arr[1]);
            echo "\nHas 'next 500'\n"."https://commons.wikimedia.org/".$part."\n";
            return "https://commons.wikimedia.org/".$part;
        }
        elseif(preg_match("/previous 500\<\/a\> \| \<a href\=\"(.*?)\" title\=\"Next 500 results\"/ims", $html, $arr)) {
            $part = str_replace("&amp;", "&", $arr[1]);
            echo "\nHas 'next 500'\n"."https://commons.wikimedia.org/".$part."\n";
            return "https://commons.wikimedia.org/".$part;
        }
        else {
            echo "\nNo 'next 500'\n";
            return false;
        }
        
    }
    function task_3c_Botanical_illustrations()
    {
        if($FILE = Functions::file_open($this->path['destination'].'Botanical_illustrations'.'.txt', 'w')) fclose($FILE); //initialize report
        if($FILE = Functions::file_open($this->path['destination'].'Botanical_illustrations'.'_download.txt', 'w')) fclose($FILE); //initialize report
        
        $flickr_BHL_albums = array('https://www.flickr.com/photos/biodivlibrary/sets/72157628019337516/', 'https://www.flickr.com/photos/biodivlibrary/sets/72157668561080736/', 
        'https://www.flickr.com/photos/biodivlibrary/albums/72157666154067184/', 'https://www.flickr.com/photos/biodivlibrary/sets/72157668837832862/', 
        'https://www.flickr.com/photos/biodivlibrary/sets/72157629695027605/', 'https://www.flickr.com/photos/biodivlibrary/albums/72157638854392084/', 
        'https://www.flickr.com/photos/biodivlibrary/albums/72157629680443310/', 'https://www.flickr.com/photos/biodivlibrary/albums/72157713173596393/');
        $flickr_BHL_albums = array('72157628019337516', '72157668561080736', '72157666154067184', '72157668837832862', '72157629695027605', 
                                   '72157638854392084', '72157629680443310', '72157713173596393');
        foreach($flickr_BHL_albums as $photoset_id) {
            echo "\n$photoset_id\n";
            self::get_images_from_album($photoset_id);
            // break; //debug only
        }
    }
    private function photo_url($photo_id, $secret, $server, $farm)
    {
        return "http://farm".$farm.".static.flickr.com/".$server."/".$photo_id."_".$secret.".jpg";
    }
    private function get_images_from_album($photoset_id) //https://www.flickr.com/services/api/flickr.photosets.getPhotos.html
    {
        $user_id = '61021753@N02'; //BioDivLibrary
        $api_key = FLICKR_API_KEY; //7856957eced5a8ddbad50f1bca0db452
        $url = "https://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=$api_key&user_id=$user_id&format=json&photoset_id=$photoset_id";
        // echo "\n$url\n";
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            // echo "\n$json\n";
            $json = str_replace('jsonFlickrApi(', '', $json);
            $json = substr($json, 0, strlen($json)-1);
            $arr = json_decode($json, true);
            // print_r($arr);
            echo " - ".count($arr['photoset']['photo'])."\n";
            foreach($arr['photoset']['photo'] as $rec) {
                // print_r($rec); exit;
                /*Array(
                    [id] => 6298833546
                    [secret] => 5a7eb31c73
                    [server] => 6222
                    [farm] => 7
                    [title] => n307_w1150
                    [isprimary] => 0
                    [ispublic] => 1
                    [isfriend] => 0
                    [isfamily] => 0
                )*/

                //start write report:
                $ret = array();
                $ret['media_url'] = self::photo_url($rec['id'], $rec['secret'], $rec['server'], $rec['farm']);
                $ret['object_id'] = $rec['id'];
                $ret['source'] = 'BioDivLibrary';
                if($ret['media_url'] && $ret['object_id']) {
                    // print_r($ret); //good debug
                    self::write_report($ret, 'Botanical_illustrations');
                }
                else exit("\ninvestigate Botanical_illustrations\n");
                
            }
        }
        else echo "\nERROR: photoset_id not found [$photoset_id]\n";
    }
    //========================================================================================================================
    function task_3a_Zoological_illustrations()
    {   /* 
        1. Grep for illustration in column 5 of these resource files: Mammals, Fishes, Entomology 
        2. Get accessURI for matching lines
        3. Download each image using accessURI
        */
        if($FILE = Functions::file_open($this->path['destination'].'Zoological_illustrations'.'.txt', 'w')) fclose($FILE); //initialize report
        if($FILE = Functions::file_open($this->path['destination'].'Zoological_illustrations'.'_download.txt', 'w')) fclose($FILE); //initialize report
        foreach($this->task_3a_DwCA as $resource_name => $dwca) {
            echo "\n $resource_name $dwca\n";
            self::process_Zoological_illustrations($resource_name, $dwca);
            // break; //debug only
        }
    }
    private function process_Zoological_illustrations($resource_name, $dwca)
    {
        if(!($info = self::extract_get_path_info($dwca))) return; //uncomment in real operation
        $this->extension_path = $info['temp_dir'];
        self::main_task_3a($resource_name);
        recursive_rmdir($info['temp_dir']);
    }
    private function main_task_3a($resource_name) //Zoological illustrations
    {
        $meta = self::get_meta_info('http://eol.org/schema/media/Document');
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...$resource_name...\n";
        foreach(new FileIterator($this->extension_path.$meta['file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 200000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            /*
            Array
            (
                [identifier] => 10396971
                [taxonID] => 1516979fecd7d12180c6e6029a09d5e5
                [type] => http://purl.org/dc/dcmitype/StillImage
                [format] => image/jpeg
                [title] => USNM 398699; Saimiri sciureus
                [description] => Cebidae; Primate xray; lateral
                [accessURI] => https://collections.nmnh.si.edu/services/media.php?env=mammals&irn=10396971
                [thumbnailURL] => https://collections.nmnh.si.edu/services/media.php?env=mammals&irn=10396971&thumb=yes
                [furtherInformationURL] => https://collections.nmnh.si.edu/search/mammals/?irn=7049433
                [CreateDate] => 24 Apr 2013
                [modified] => 19 Dec 2019
                [language] => En
                [Rating] => 2
                [audience] => General public
                [UsageTerms] => http://creativecommons.org/licenses/by-nc-sa/3.0/
                [rights] => This image was obtained from the Smithsonian Institution. Unless otherwise noted, this image or its contents may be protected by international copyright laws.
                [Owner] => Smithsonian Institution, National Museum of Natural History, Department of Vertebrate Zoology, Division of Mammals
                [agentID] => 5677f0c8bb82480769d1803bf325dbba
                [LocationCreated] => Locality Unknown
            )
            */
            /* "So you could ask Eli to make you NMNH illustration bundles for 
            Mammals (72 images), 
            Fishes (3299 images), 
            and Entomology (3720 images) by grepping for "illustration" (not case sensitive) in column 5 (title)."
            */
            if(stripos($rec['title'], "illustrat") !== false) { //string is found
                $ret = array();
                $ret['media_url'] = $rec['accessURI'];
                $ret['object_id'] = $rec['identifier'];
                if(preg_match("/licenses\/(.*?)\//ims", $rec['UsageTerms'], $arr)) $ret['license'] = $arr[1];
                $ret['source'] = $resource_name;
                if($ret['media_url'] && $ret['object_id'] && $ret['license']) {
                    // print_r($ret); //good debug
                    self::write_report($ret, 'Zoological_illustrations');
                }
                else exit("\ninvestigate [$page_id] [$resource_id]\n");
            }
        }
    }
    private function extract_get_path_info($dwca)
    {
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca, "taxon.tab", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        // */
        /* debug only
        $paths = Array
        (
            'archive_path' => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_20494/",
            'temp_dir' => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_20494/"
        );
        */
        print_r($paths);
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $tables['taxa'] = 'taxon.tab';
        return array("temp_dir" => $temp_dir, "tables" => $tables);
    }
    private function get_meta_info($row_type = false)
    {
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($this->extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
        if($GLOBALS['ENV_DEBUG']) print_r($meta);
        return $meta;
    }
    //========================================================================================================================
    function task_1_Herbarium_Sheets()
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
        if($FILE = Functions::file_open($this->path['destination'].'herbarium_sheets'.'.txt', 'w')) fclose($FILE); //initialize report
        if($FILE = Functions::file_open($this->path['destination'].'herbarium_sheets'.'_download.txt', 'w')) fclose($FILE); //initialize report

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
                        $ret['source'] = "EOL";
                        if($ret['media_url'] && $ret['object_id'] && $ret['license']) {
                            // print_r($ret); //good debug
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
        if($FILE2 = Functions::file_open($this->path['destination'].$report.'_download.txt', 'a')) {
            fwrite($FILE2, $ret['media_url']."\n");
            fclose($FILE2);
        }
        
        if($report == 'herbarium_sheets') {
            if($this->total_write >= 3000) exit("\n3000 saved rows. Will stop process.\n");
        }
    }
}
?>