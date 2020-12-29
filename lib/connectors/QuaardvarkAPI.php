<?php
namespace php_active_record;
/* connector:
*/
class QuaardvarkAPI
{
    function __construct($folder = null)
    {
        /*
        if($folder) {
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        */
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 172800, 'download_attempts' => 2, 'delay_in_minutes' => 1);
        $this->download_options["expire_seconds"] = 60*60*24*25;
        $this->debug = array();
        $this->url['all species with habitat keywords'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FDE-F3B2-0001-913C-B28812191D82/?start=';
        /*
        https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FDE-F3B2-0001-913C-B28812191D82/?start=1
        https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FF7-B855-0001-DDF1-12C01181A670/?start=201
        https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FF7-B855-0001-DDF1-12C01181A670/?start=401
        */
    }
    public function start()
    {
        if($total_pages = self::get_total_number_of_pages()) {
            $loops = ceil($total_pages/200);
            echo "\n total_pages: [$total_pages]\n loops: [$loops]\n";
            $sum = 1;
            for ($i = 1; $i <= $loops; $i++) {
                echo "\n$i. $sum";
                $url = $this->url['all species with habitat keywords'].$sum;
                if($html = Functions::lookup_with_cache($url, $this->download_options)) { //hits="6369"
                    $recs = self::parse_page($html);
                }
                $sum = $sum + 200;
            }
            exit("\n-end-\n");
        }
    }
    private function parse_page($html)
    {
        $left = '<table xmlns:media="urn:animaldiversity.org:templates:media"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)<\/table>/ims", $html, $a)) {
            $main_block = $a[1];

            $fields = array();
            if(preg_match_all("/<th>(.*?)<\/th>/ims", $main_block, $a1)) $fields = $a1[1];
            if(count($fields) != 9) exit("\nInvestigate fields <th> tags\n");

            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $main_block, $a2)) {
                $rows201 = $a2[1];
                foreach($rows201 as $row) {
                    $row = str_replace('<td type="sequence"/>', '<td type="sequence"></td>', $row);
                    if(preg_match_all("/<td(.*?)<\/td>/ims", $row, $a3)) {
                        $cols = $a3[1];
                        // print_r($cols); exit; //good debug
                        $ret = array();
                        foreach($cols as $col) {
                            $tmp = strip_tags("<td".$col, "<span>");
                            if(preg_match_all("/<span>(.*?)<\/span>/ims", $tmp, $a4)) {
                                $tmp = $a4[1];
                                $tmp = implode(" | ", $tmp);
                            }
                            $ret[] = $tmp;
                        }
                        $ret = array_map('trim', $ret);
                        print_r($ret); //exit;
                    }
                }
            }
            
            
            exit("\naaa\n");
        }
        
    }
    private function get_total_number_of_pages()
    {
        $url = $this->url['all species with habitat keywords']."1";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) { //hits="6369"
            if(preg_match("/hits=\"(.*?)\"/ims", $html, $a)) {
                return $a[1];
            }
            else exit("\nNo hits, investigate URL: [$url]\n");
        }
    }
}
?>