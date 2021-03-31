<?php
namespace php_active_record;
/* connector: [process_SI_pdfs.php] */
class SmithsonianPDFsAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('resource_id' => "SI", 'timeout' => 172800, 'expire_seconds' => false, 'download_wait_time' => 1000000); // expire_seconds = every 45 days in normal operation
        $this->debug = array();
        
        // https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=0&etal=-1&order=ASC
        // https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=20&etal=-1&order=ASC
        $this->web['PDFs per page'] = "https://repository.si.edu/handle/10088/5097/browse?rpp=20&sort_by=2&type=dateissued&offset=NUM_OFFSET&etal=-1&order=ASC";
        $this->web['domain'] = 'https://repository.si.edu';

        if(Functions::is_production()) $this->path['working_dir'] = '/extra/other_files/Smithsonian/epub/';
        else                           $this->path['working_dir'] = '/Volumes/AKiTiO4/other_files/Smithsonian/epub/';
    }
    function start()
    {
        $pdfs_info = self::get_all_pdfs();
        // print_r($pdfs_info); exit;
        /*Array(
            [0] => Array(
                    [url] => https://repository.si.edu//handle/10088/5292
                    [title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean)
            [1] => Array(
                    [url] => https://repository.si.edu//handle/10088/5349
                    [title] => Deep-sea Cerviniidae (Copepoda: Harpacticoida) from the Western Indian Ocean, collected with RV Anton Bruun in 1964)
        */
        $i = -1;
        foreach($pdfs_info as $info) { $i++;
            self::process_a_pdf($info);
        }
    }
    private function process_a_pdf($info)
    {
        // print_r($info); exit;
        /*Array(
            [url] => https://repository.si.edu//handle/10088/5292
            [title] => Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean
        )*/
        $epub_info = self::get_epub_info($info['url']);
        /*Array(
            [filename] => SCtZ-0007.epub
            [url] => https://repository.si.edu/bitstream/handle/10088/5292/SCtZ-0007.epub
        )*/
        $destination = $this->path['working_dir'].$epub_info['filename'];
        $cmd = "wget -nc ".$epub_info['url']." -O $destination";
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);
        exit;
    }
    private function get_epub_info($url)
    {   /*
        <div style="height: 80px;" class="file-link">
        <a href="/bitstream/handle/10088/5551/SCtZ-0081.epub?sequence=2&amp;isAllowed=y">View/<wbr xmlns:i18n="http://apache.org/cocoon/i18n/2.1" />Open</a>
        </div>
        */
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/".preg_quote('class="file-link">', '/')."(.*?)<\/div>/ims", $html, $a)) {
                // print_r($a[1]); exit;
                foreach($a[1] as $line) {
                    if(stripos($line, ".epub") !== false) { //string is found
                        // exit("\n$line\n");
                        /* <a href="/bitstream/handle/10088/5292/SCtZ-0007.epub?sequence=3&amp;isAllowed=y">View/<wbr xmlns:i18n="http://apache.org/cocoon/i18n/2.1" />Open</a> */
                        if(preg_match("/href=\"(.*?)\"/ims", $line, $a)) {
                            $tmp = $this->web['domain'].$a[1];
                            $ret = array();
                            $ret['filename'] = pathinfo($tmp, PATHINFO_FILENAME).".epub";
                            $ret['url'] = pathinfo($tmp, PATHINFO_DIRNAME)."/".$ret['filename'];
                            // print_r($ret); exit;
                            return $ret;
                        }
                    }
                }
            }
            
        }
    }
    private function get_all_pdfs()
    {
        $total_pdfs = self::get_total_pdfs(); //exit("\n[$total_pdfs]\n");
        $total_pages = ceil($total_pdfs/20);
        // $total_pages = ceil(661/20); //total_pages should be 34 OK
        echo "\ntotal pages: [$total_pages]\n"; //exit;
        $page = 0; $offset = 0;
        while($page < $total_pages) { $page++; echo "\n[$page][$offset]\n";
            $url = str_replace("NUM_OFFSET", $offset, $this->web['PDFs per page']);
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                /*
                <div class="artifact-title">
                <a href="/handle/10088/5292">Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean</a>
                <span class="Z3988"
                */
                if(preg_match_all("/".preg_quote('<div class="artifact-title">', '/')."(.*?)<span/ims", $html, $a)) {
                    $temp = array_map('trim', $a[1]);
                    // print_r($temp); exit;
                    /*Array(
                        [0] => <a href="/handle/10088/5292">Recent ostracodes of the family Pontocyprididae chiefly from the Indian Ocean</a>
                        [1] => <a href="/handle/10088/5349">Deep-sea Cerviniidae (Copepoda: Harpacticoida) from the Western Indian Ocean, collected with RV Anton Bruun in 1964</a>
                    */
                    foreach($temp as $t) {
                        $rec = array();
                        if(preg_match("/href=\"(.*?)\"/ims", $t, $a)) $rec['url'] = $this->web['domain']."/".$a[1];
                        if(preg_match("/\">(.*?)<\/a>/ims", $t, $a)) $rec['title'] = $a[1];
                        $final[] = $rec;
                    }
                }
            }
            $offset = $offset + 20;
            if($page == 5) break; //debug only
        }
        return $final;
    }
    private function get_total_pdfs()
    {
        $url = str_replace("NUM_OFFSET", 0, $this->web['PDFs per page']);
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            /*Now showing items 1-20 of 660</p>*/
            if(preg_match("/Now showing items 1-20 of (.*?)<\/p>/ims", $html, $a)) return trim($a[1]);
        }
    }
}